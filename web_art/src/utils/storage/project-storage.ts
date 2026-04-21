import { StorageConfig } from './storage-config'

const PROJECT_LOCAL_STORAGE_KEYS = new Set([
  StorageConfig.VERSION_KEY,
  StorageConfig.THEME_KEY,
  StorageConfig.LAST_USER_ID_KEY,
  StorageConfig.RESPONSIVE_MENU_TYPE_KEY
])

const PROJECT_SESSION_STORAGE_KEYS = ['iframeRoutes']

function isProjectLocalStorageKey(key: string): boolean {
  return PROJECT_LOCAL_STORAGE_KEYS.has(key) || StorageConfig.isVersionedKey(key)
}

export function clearProjectStorage(): {
  localRemoved: number
  sessionRemoved: number
} {
  let localRemoved = 0
  let sessionRemoved = 0

  for (const key of Object.keys(localStorage)) {
    if (!isProjectLocalStorageKey(key)) {
      continue
    }

    localStorage.removeItem(key)
    localRemoved += 1
  }

  for (const key of PROJECT_SESSION_STORAGE_KEYS) {
    if (sessionStorage.getItem(key) === null) {
      continue
    }

    sessionStorage.removeItem(key)
    sessionRemoved += 1
  }

  return {
    localRemoved,
    sessionRemoved
  }
}
