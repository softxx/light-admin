export const DEFAULT_LOGIN_REDIRECT = '/dashboard'

function getRedirectValue(redirect: unknown): string | undefined {
  return typeof redirect === 'string' ? redirect : undefined
}

function isAllowedLoginRedirect(path: string): boolean {
  return path === DEFAULT_LOGIN_REDIRECT || path.startsWith(`${DEFAULT_LOGIN_REDIRECT}/`)
}

export function normalizeLoginRedirect(redirect: unknown): string {
  const value = getRedirectValue(redirect)

  if (value && isAllowedLoginRedirect(value)) {
    return value
  }

  return DEFAULT_LOGIN_REDIRECT
}

export function shouldNormalizeLoginRedirect(redirect: unknown): boolean {
  return getRedirectValue(redirect) !== normalizeLoginRedirect(redirect)
}
