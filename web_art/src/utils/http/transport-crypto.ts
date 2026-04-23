import type { AxiosRequestConfig, AxiosResponse } from 'axios'
import { sm2, sm3, sm4 } from 'sm-crypto-v2'

const encoder = new TextEncoder()
const decoder = new TextDecoder()
const DEFAULT_QUERY_PARAM = '__enc'
const BYPASS_PATHS = new Set([
  '/adminapi/crypto/meta',
  '/adminapi/system_setting/public',
  '/adminapi/upload/file',
  '/adminapi/upload/image',
  '/adminapi/upload/attachment',
  '/adminapi/login_log/export'
])

const { VITE_API_URL, VITE_WITH_CREDENTIALS } = import.meta.env

interface TransportCryptoMeta {
  enabled: boolean
  v: number
  alg: string
  kid: string
  sm2_public_key: string
  sm2_asn1?: boolean
  key_length: number
  iv_length: number
}

interface TransportRequestEnvelope {
  v: number
  alg: string
  kid: string
  ts: number
  nonce: string
  ek: string
  iv: string
  ct: string
  mac: string
}

interface TransportResponseEnvelope {
  v: number
  enc: number
  ts: number
  nonce: string
  iv: string
  ct: string
  mac: string
}

interface TransportOriginalPayload {
  data: unknown
  params: unknown
}

export interface TransportCryptoContext {
  key: Uint8Array
  path: string
  requestNonce: string
}

export interface TransportCryptoRequestConfig extends AxiosRequestConfig {
  skipEncryption?: boolean
  _transportCryptoContext?: TransportCryptoContext
  _transportOriginalPayload?: TransportOriginalPayload
  _transportMetaRetried?: boolean
}

let cachedMeta: TransportCryptoMeta | null = null
let cachedMetaPromise: Promise<TransportCryptoMeta> | null = null

export function clearTransportCryptoMetaCache() {
  cachedMeta = null
  cachedMetaPromise = null
}

export async function fetchTransportCryptoMeta(forceRefresh = false): Promise<TransportCryptoMeta> {
  if (forceRefresh) {
    clearTransportCryptoMetaCache()
  }

  if (cachedMeta) {
    return cachedMeta
  }

  if (!cachedMetaPromise) {
    cachedMetaPromise = requestTransportCryptoMeta().then((meta) => {
      cachedMeta = meta
      return meta
    })
  }

  try {
    return await cachedMetaPromise
  } finally {
    cachedMetaPromise = null
  }
}

export function shouldBypassTransportEncryption(config: AxiosRequestConfig): boolean {
  if ((config as TransportCryptoRequestConfig).skipEncryption) {
    return true
  }

  if (config.responseType === 'blob' || config.responseType === 'arraybuffer') {
    return true
  }

  if (typeof FormData !== 'undefined' && config.data instanceof FormData) {
    return true
  }

  return BYPASS_PATHS.has(resolveRequestPath(config))
}

export function isTransportMetaInvalidPayload(data: unknown): boolean {
  return isObject(data) && Number(data.code) === 4602
}

export async function encryptTransportRequest(
  config: TransportCryptoRequestConfig
): Promise<TransportCryptoRequestConfig> {
  if (shouldBypassTransportEncryption(config)) {
    return config
  }

  restoreOriginalPayload(config)

  if (!config._transportOriginalPayload) {
    config._transportOriginalPayload = {
      data: safeClone(config.data),
      params: safeClone(config.params)
    }
  }

  const meta = await fetchTransportCryptoMeta()
  if (!meta.enabled) {
    return config
  }

  const method = (config.method || 'GET').toUpperCase()
  const path = resolveRequestPath(config)
  const payload = method === 'GET' ? safeClone(config.params ?? {}) : safeClone(config.data ?? {})

  const requestNonce = bytesToHex(randomBytes(16))
  const timestamp = Math.floor(Date.now() / 1000)
  const sm4Key = randomBytes(meta.key_length)
  const iv = randomBytes(meta.iv_length)
  const aad = encoder.encode(buildRequestAad(method, path, timestamp, requestNonce))
  const plaintext = encoder.encode(JSON.stringify({ data: payload ?? {} }))
  const ciphertext = normalizeByteArray(
    sm4.encrypt(plaintext, sm4Key, {
      mode: 'cbc',
      iv,
      output: 'array'
    })
  )
  const mac = signPayload(sm4Key, aad, iv, ciphertext)

  const envelope: TransportRequestEnvelope = {
    v: meta.v,
    alg: meta.alg,
    kid: meta.kid,
    ts: timestamp,
    nonce: requestNonce,
    ek: sm2.doEncrypt(sm4Key, meta.sm2_public_key, 1, {
      asn1: Boolean(meta.sm2_asn1)
    }),
    iv: bytesToBase64Url(iv),
    ct: bytesToBase64Url(ciphertext),
    mac
  }

  config._transportCryptoContext = {
    key: sm4Key,
    path,
    requestNonce
  }

  if (method === 'GET') {
    config.params = {
      [DEFAULT_QUERY_PARAM]: encodeJsonBase64Url(envelope)
    }
    config.data = undefined
  } else {
    config.params = undefined
    config.data = envelope
  }

  return config
}

export function decryptTransportResponse<T>(response: AxiosResponse<T>): AxiosResponse<T> {
  if (!isTransportResponseEnvelope(response.data)) {
    return response
  }

  const config = response.config as TransportCryptoRequestConfig
  const cryptoContext = config._transportCryptoContext

  if (!cryptoContext) {
    throw new Error('Transport crypto context is missing for the encrypted response.')
  }

  const iv = base64UrlToBytes(response.data.iv)
  const ciphertext = base64UrlToBytes(response.data.ct)
  const aad = encoder.encode(
    buildResponseAad(
      cryptoContext.path,
      cryptoContext.requestNonce,
      Number(response.data.ts),
      String(response.data.nonce)
    )
  )
  const expectedMac = signPayload(cryptoContext.key, aad, iv, ciphertext)
  if (expectedMac !== response.data.mac.toLowerCase()) {
    throw new Error('Transport SM3 verification failed.')
  }

  const plaintext = sm4.decrypt(ciphertext, cryptoContext.key, {
    mode: 'cbc',
    iv,
    output: 'string'
  })

  const text = typeof plaintext === 'string' ? plaintext : decoder.decode(plaintext)
  response.data = JSON.parse(text) as T
  return response
}

function requestTransportCryptoMeta(): Promise<TransportCryptoMeta> {
  return fetch(resolveApiUrl('/crypto/meta'), {
    method: 'GET',
    credentials: VITE_WITH_CREDENTIALS === 'true' ? 'include' : 'same-origin',
    headers: {
      Accept: 'application/json'
    }
  }).then(async (response) => {
    const body = await response.json()

    if (!response.ok || Number(body?.code) !== 1 || !body?.data?.sm2_public_key) {
      throw new Error('Failed to load transport crypto metadata.')
    }

    return body.data as TransportCryptoMeta
  })
}

function resolveApiUrl(path: string): string {
  if (/^https?:\/\//i.test(path)) {
    return path
  }

  const base = typeof VITE_API_URL === 'string' ? VITE_API_URL : ''
  const normalizedPath = path.startsWith('/') ? path : `/${path}`

  if (!base) {
    return normalizedPath
  }

  if (/^https?:\/\//i.test(base)) {
    return new URL(normalizedPath, base.endsWith('/') ? base : `${base}/`).toString()
  }

  const normalizedBase = base.endsWith('/') ? base.slice(0, -1) : base
  return normalizedPath.startsWith(normalizedBase)
    ? normalizedPath
    : `${normalizedBase}${normalizedPath}`
}

function resolveRequestPath(config: AxiosRequestConfig): string {
  const requestUrl = typeof config.url === 'string' ? config.url : '/'

  if (/^https?:\/\//i.test(requestUrl)) {
    return normalizePath(new URL(requestUrl).pathname)
  }

  const baseUrl = typeof config.baseURL === 'string' ? config.baseURL : VITE_API_URL || ''
  const normalizedUrl = requestUrl.startsWith('/') ? requestUrl : `/${requestUrl}`

  if (!baseUrl) {
    return normalizePath(normalizedUrl)
  }

  if (/^https?:\/\//i.test(baseUrl)) {
    return normalizePath(new URL(normalizedUrl, baseUrl.endsWith('/') ? baseUrl : `${baseUrl}/`).pathname)
  }

  const normalizedBase = baseUrl.endsWith('/') ? baseUrl.slice(0, -1) : baseUrl
  if (normalizedUrl === normalizedBase || normalizedUrl.startsWith(`${normalizedBase}/`)) {
    return normalizePath(normalizedUrl)
  }

  return normalizePath(`${normalizedBase}${normalizedUrl}`)
}

function normalizePath(path: string): string {
  const normalized = path.startsWith('/') ? path : `/${path}`
  return normalized === '/' ? normalized : normalized.replace(/\/+$/, '')
}

function restoreOriginalPayload(config: TransportCryptoRequestConfig) {
  if (!config._transportOriginalPayload) {
    return
  }

  config.data = safeClone(config._transportOriginalPayload.data)
  config.params = safeClone(config._transportOriginalPayload.params)
}

function safeClone<T>(value: T): T {
  if (value === null || value === undefined) {
    return value
  }

  if (typeof structuredClone === 'function') {
    return structuredClone(value)
  }

  if (typeof FormData !== 'undefined' && value instanceof FormData) {
    return value
  }

  try {
    return JSON.parse(JSON.stringify(value)) as T
  } catch {
    return value
  }
}

function buildRequestAad(method: string, path: string, timestamp: number, nonce: string): string {
  return ['REQ', method.toUpperCase(), path, String(timestamp), nonce].join('\n')
}

function buildResponseAad(
  path: string,
  requestNonce: string,
  timestamp: number,
  nonce: string
): string {
  return ['RES', path, requestNonce, String(timestamp), nonce].join('\n')
}

function signPayload(
  key: Uint8Array,
  aad: Uint8Array,
  iv: Uint8Array,
  ciphertext: Uint8Array
): string {
  return sm3(buildMacPayload(aad, iv, ciphertext), { key }).toLowerCase()
}

function buildMacPayload(aad: Uint8Array, iv: Uint8Array, ciphertext: Uint8Array): Uint8Array {
  return concatBytes(encodeUint32(aad.length), aad, encodeUint32(iv.length), iv, encodeUint32(ciphertext.length), ciphertext)
}

function encodeUint32(value: number): Uint8Array {
  return new Uint8Array([
    (value >>> 24) & 0xff,
    (value >>> 16) & 0xff,
    (value >>> 8) & 0xff,
    value & 0xff
  ])
}

function concatBytes(...chunks: Uint8Array[]): Uint8Array {
  const totalLength = chunks.reduce((sum, chunk) => sum + chunk.length, 0)
  const merged = new Uint8Array(totalLength)
  let offset = 0

  for (const chunk of chunks) {
    merged.set(chunk, offset)
    offset += chunk.length
  }

  return merged
}

function randomBytes(length: number): Uint8Array {
  const bytes = new Uint8Array(length)
  crypto.getRandomValues(bytes)
  return bytes
}

function normalizeByteArray(value: unknown): Uint8Array {
  if (value instanceof Uint8Array) {
    return value
  }

  if (Array.isArray(value)) {
    return Uint8Array.from(value)
  }

  throw new Error('SM4-CBC returned an invalid byte array.')
}

function bytesToHex(bytes: Uint8Array): string {
  return Array.from(bytes)
    .map((value) => value.toString(16).padStart(2, '0'))
    .join('')
}

function bytesToBase64Url(bytes: Uint8Array): string {
  const chunkSize = 0x8000
  let binary = ''

  for (let index = 0; index < bytes.length; index += chunkSize) {
    binary += String.fromCharCode(...bytes.subarray(index, index + chunkSize))
  }

  return toBase64Url(binary)
}

function encodeJsonBase64Url(value: unknown): string {
  return bytesToBase64Url(encoder.encode(JSON.stringify(value)))
}

function base64UrlToBytes(value: string): Uint8Array {
  const normalized = value.replace(/-/g, '+').replace(/_/g, '/')
  const padded = normalized.padEnd(Math.ceil(normalized.length / 4) * 4, '=')
  const binary = atob(padded)
  const bytes = new Uint8Array(binary.length)

  for (let index = 0; index < binary.length; index++) {
    bytes[index] = binary.charCodeAt(index)
  }

  return bytes
}

function toBase64Url(binary: string): string {
  return btoa(binary).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/g, '')
}

function isObject(value: unknown): value is Record<string, any> {
  return typeof value === 'object' && value !== null
}

function isTransportResponseEnvelope(data: unknown): data is TransportResponseEnvelope {
  return (
    isObject(data) &&
    Number(data.enc) === 1 &&
    typeof data.iv === 'string' &&
    typeof data.ct === 'string' &&
    typeof data.mac === 'string'
  )
}
