import { fetchLogout } from '@/api/auth'
import { useUserStore } from '@/store/modules/user'
import { clearProjectStorage } from '@/utils/storage'

export async function notifyServerLogout(refreshToken?: string): Promise<void> {
  if (!refreshToken) {
    return
  }

  await fetchLogout(refreshToken).catch(() => undefined)
}

export async function logoutCurrentSession(): Promise<void> {
  const userStore = useUserStore()
  await notifyServerLogout(userStore.refreshToken)
  userStore.logOut()
}

export async function clearBrowserCacheAndLogout(): Promise<void> {
  const userStore = useUserStore()
  await notifyServerLogout(userStore.refreshToken)
  userStore.logOut()
  clearProjectStorage()
}
