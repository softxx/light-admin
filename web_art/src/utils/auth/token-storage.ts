import { StorageConfig } from '@/utils/storage/storage-config'
import { StorageKeyManager } from '@/utils/storage/storage-key-manager'

const ACCESS_TOKEN_COOKIE_KEY = 'admin_accessToken'
const REFRESH_TOKEN_COOKIE_KEY = 'admin_refreshToken'
const COOKIE_PATH = '/'
const USER_STORAGE_KEY = 'user'

let tokenStorageInitialized = false

function canUseBrowserStorage() {
  return typeof window !== 'undefined' && typeof document !== 'undefined'
}

function normalizeToken(token = ''): string {
  return token.replace(/^Bearer\s+/i, '').trim()
}

function withBearer(token = ''): string {
  const normalizedToken = normalizeToken(token)

  if (!normalizedToken) {
    return ''
  }

  return `Bearer ${normalizedToken}`
}

function getCookieValue(name: string): string {
  if (!canUseBrowserStorage()) {
    return ''
  }

  const encodedName = `${encodeURIComponent(name)}=`

  for (const segment of document.cookie.split('; ')) {
    if (segment.startsWith(encodedName)) {
      return decodeURIComponent(segment.slice(encodedName.length))
    }
  }

  return ''
}

function setCookie(name: string, value: string, maxAgeSeconds?: number): void {
  if (!canUseBrowserStorage()) {
    return
  }

  const cookieParts = [
    `${encodeURIComponent(name)}=${encodeURIComponent(value)}`,
    `Path=${COOKIE_PATH}`,
    'SameSite=Lax'
  ]

  if (typeof maxAgeSeconds === 'number' && Number.isFinite(maxAgeSeconds)) {
    cookieParts.push(`Max-Age=${Math.max(0, Math.floor(maxAgeSeconds))}`)
  }

  if (window.location.protocol === 'https:') {
    cookieParts.push('Secure')
  }

  document.cookie = cookieParts.join('; ')
}

function removeCookie(name: string): void {
  setCookie(name, '', 0)
}

function decodeBase64Url(value: string): string {
  const normalizedValue = value.replace(/-/g, '+').replace(/_/g, '/')
  const paddedValue = normalizedValue.padEnd(Math.ceil(normalizedValue.length / 4) * 4, '=')

  return window.atob(paddedValue)
}

function parseJwtPayload(token: string): Record<string, unknown> | null {
  const normalizedToken = normalizeToken(token)
  const segments = normalizedToken.split('.')

  if (segments.length < 2) {
    return null
  }

  try {
    return JSON.parse(decodeBase64Url(segments[1]))
  } catch (error) {
    console.warn('[Auth] Failed to parse token payload from cookie storage.', error)
    return null
  }
}

function getTokenMaxAgeSeconds(token: string): number | undefined {
  const payload = parseJwtPayload(token)
  const expiresAt = Number(payload?.exp)

  if (!Number.isFinite(expiresAt)) {
    return undefined
  }

  return Math.max(0, expiresAt - Math.floor(Date.now() / 1000))
}

function getCookieToken(name: string): string {
  ensureTokenStorageInitialized()
  return normalizeToken(getCookieValue(name))
}

function setCookieToken(name: string, token: string, maxAgeSeconds?: number): void {
  const normalizedToken = normalizeToken(token)

  if (!normalizedToken) {
    removeCookie(name)
    return
  }

  setCookie(name, normalizedToken, maxAgeSeconds)
}

function getUserStorageKey(): string {
  return new StorageKeyManager().getStorageKey(USER_STORAGE_KEY)
}

function readLegacyUserStorage(): Record<string, unknown> | null {
  if (!canUseBrowserStorage()) {
    return null
  }

  const rawValue = localStorage.getItem(getUserStorageKey())
  if (!rawValue) {
    return null
  }

  try {
    return JSON.parse(rawValue) as Record<string, unknown>
  } catch (error) {
    console.warn('[Auth] Failed to parse persisted user store while migrating tokens.', error)
    return null
  }
}

function persistSanitizedUserStorage(userState: Record<string, unknown>): void {
  if (!canUseBrowserStorage()) {
    return
  }

  const sanitizedState = { ...userState }
  delete sanitizedState.accessToken
  delete sanitizedState.refreshToken

  localStorage.setItem(getUserStorageKey(), JSON.stringify(sanitizedState))
}

function migrateLegacyTokensToCookies(): void {
  const userState = readLegacyUserStorage()
  if (!userState) {
    return
  }

  const storedAccessToken = normalizeToken(String(userState.accessToken ?? ''))
  const storedRefreshToken = normalizeToken(String(userState.refreshToken ?? ''))
  const currentAccessToken = normalizeToken(getCookieValue(ACCESS_TOKEN_COOKIE_KEY))
  const currentRefreshToken = normalizeToken(getCookieValue(REFRESH_TOKEN_COOKIE_KEY))

  if (!currentAccessToken && storedAccessToken) {
    const accessTokenMaxAge =
      getTokenMaxAgeSeconds(storedRefreshToken) ?? getTokenMaxAgeSeconds(storedAccessToken)
    setCookieToken(ACCESS_TOKEN_COOKIE_KEY, storedAccessToken, accessTokenMaxAge)
  }

  if (!currentRefreshToken && storedRefreshToken) {
    setCookieToken(
      REFRESH_TOKEN_COOKIE_KEY,
      storedRefreshToken,
      getTokenMaxAgeSeconds(storedRefreshToken)
    )
  }

  if ('accessToken' in userState || 'refreshToken' in userState) {
    persistSanitizedUserStorage(userState)
  }

  const legacySystemStorage = localStorage.getItem(StorageConfig.generateLegacyKey())
  if (!legacySystemStorage) {
    return
  }

  try {
    const systemState = JSON.parse(legacySystemStorage) as Record<string, unknown>
    const legacyUser = (systemState.user ?? {}) as Record<string, unknown>
    if (!('accessToken' in legacyUser) && !('refreshToken' in legacyUser)) {
      return
    }

    delete legacyUser.accessToken
    delete legacyUser.refreshToken
    systemState.user = legacyUser
    localStorage.setItem(StorageConfig.generateLegacyKey(), JSON.stringify(systemState))
  } catch (error) {
    console.warn('[Auth] Failed to sanitize legacy system storage during token migration.', error)
  }
}

function ensureTokenStorageInitialized(): void {
  if (tokenStorageInitialized || !canUseBrowserStorage()) {
    return
  }

  tokenStorageInitialized = true
  migrateLegacyTokensToCookies()
}

export function initializeTokenStorage() {
  ensureTokenStorageInitialized()

  return {
    accessToken: normalizeToken(getCookieValue(ACCESS_TOKEN_COOKIE_KEY)),
    refreshToken: normalizeToken(getCookieValue(REFRESH_TOKEN_COOKIE_KEY))
  }
}

export function getAccessToken(): string {
  return getCookieToken(ACCESS_TOKEN_COOKIE_KEY)
}

export function getRefreshToken(): string {
  return getCookieToken(REFRESH_TOKEN_COOKIE_KEY)
}

export function getAccessTokenHeaderValue(): string {
  return withBearer(getAccessToken())
}

export function getRefreshTokenHeaderValue(): string {
  return withBearer(getRefreshToken())
}

export function setTokenCookies(accessToken: string, refreshToken?: string): void {
  ensureTokenStorageInitialized()

  const normalizedAccessToken = normalizeToken(accessToken)
  const normalizedRefreshToken =
    typeof refreshToken === 'string' ? normalizeToken(refreshToken) : ''
  const accessTokenMaxAge =
    getTokenMaxAgeSeconds(normalizedRefreshToken) ?? getTokenMaxAgeSeconds(normalizedAccessToken)

  setCookieToken(ACCESS_TOKEN_COOKIE_KEY, normalizedAccessToken, accessTokenMaxAge)

  if (typeof refreshToken === 'string') {
    setCookieToken(
      REFRESH_TOKEN_COOKIE_KEY,
      normalizedRefreshToken,
      getTokenMaxAgeSeconds(normalizedRefreshToken)
    )
  }
}

export function clearTokenCookies(): void {
  ensureTokenStorageInitialized()
  removeCookie(ACCESS_TOKEN_COOKIE_KEY)
  removeCookie(REFRESH_TOKEN_COOKIE_KEY)
}
