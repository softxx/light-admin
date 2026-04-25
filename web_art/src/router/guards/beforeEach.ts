import type { NavigationGuardNext, RouteLocationNormalized, Router } from 'vue-router'
import { nextTick } from 'vue'
import NProgress from 'nprogress'
import { fetchGetUserInfo } from '@/api/auth'
import { useCommon } from '@/hooks/core/useCommon'
import { useMenuStore } from '@/store/modules/menu'
import { useSettingStore } from '@/store/modules/setting'
import { useUserStore } from '@/store/modules/user'
import { useWorktabStore } from '@/store/modules/worktab'
import { setWorktab } from '@/utils/navigation'
import {
  DEFAULT_LOGIN_REDIRECT,
  normalizeLoginRedirect,
  shouldNormalizeLoginRedirect
} from '@/utils/navigation/login-redirect'
import { isHttpError } from '@/utils/http/error'
import { ApiStatus } from '@/utils/http/status'
import { setPageTitle } from '@/utils/router'
import { loadingService } from '@/utils/ui'
import { IframeRouteManager, MenuProcessor, RoutePermissionValidator, RouteRegistry } from '../core'
import { staticRoutes } from '../routes/staticRoutes'
import { RoutesAlias } from '../routesAlias'

let routeRegistry: RouteRegistry | null = null
const menuProcessor = new MenuProcessor()
let pendingLoading = false
let routeInitFailed = false
let routeInitInProgress = false

export function getPendingLoading(): boolean {
  return pendingLoading
}

export function resetPendingLoading(): void {
  pendingLoading = false
}

export function getRouteInitFailed(): boolean {
  return routeInitFailed
}

export function resetRouteInitState(): void {
  routeInitFailed = false
  routeInitInProgress = false
}

export function setupBeforeEachGuard(router: Router): void {
  routeRegistry = new RouteRegistry(router)

  router.beforeEach(
    async (
      to: RouteLocationNormalized,
      from: RouteLocationNormalized,
      next: NavigationGuardNext
    ) => {
      try {
        await handleRouteGuard(to, from, next, router)
      } catch (error) {
        console.error('[RouteGuard] Failed to process route guard:', error)
        closeLoading()
        next({ name: 'Exception500' })
      }
    }
  )
}

function closeLoading(): void {
  if (!pendingLoading) {
    return
  }

  nextTick(() => {
    loadingService.hideLoading()
    pendingLoading = false
  })
}

async function handleRouteGuard(
  to: RouteLocationNormalized,
  from: RouteLocationNormalized,
  next: NavigationGuardNext,
  router: Router
): Promise<void> {
  const settingStore = useSettingStore()
  const userStore = useUserStore()

  if (settingStore.showNprogress) {
    NProgress.start()
  }

  if (handleRootEntry(to, next, userStore)) {
    return
  }

  if (!handleLoginStatus(to, userStore, next)) {
    return
  }

  if (routeInitFailed) {
    if (to.matched.length > 0) {
      next()
    } else {
      next({ name: 'Exception500', replace: true })
    }
    return
  }

  if (!routeRegistry?.isRegistered() && userStore.isLogin) {
    if (routeInitInProgress) {
      next(false)
      return
    }

    await handleDynamicRoutes(to, next, router)
    return
  }

  if (to.matched.length > 0) {
    setWorktab(to)
    setPageTitle(to)
    next()
    return
  }

  next({ name: 'Exception404' })
}

function handleLoginStatus(
  to: RouteLocationNormalized,
  userStore: ReturnType<typeof useUserStore>,
  next: NavigationGuardNext
): boolean {
  if (userStore.isLogin) {
    return true
  }

  if (to.path === RoutesAlias.Login) {
    if (!shouldNormalizeLoginRedirect(to.query.redirect)) {
      return true
    }

    next({
      name: 'Login',
      query: {
        ...to.query,
        redirect: normalizeLoginRedirect(to.query.redirect)
      },
      replace: true
    })
    return false
  }

  if (isStaticRoute(to.path)) {
    return true
  }

  if (isGuestDashboardEntry(to.path)) {
    next({
      name: 'Login',
      query: { redirect: DEFAULT_LOGIN_REDIRECT },
      replace: true
    })
    return false
  }

  return true
}

function handleRootEntry(
  to: RouteLocationNormalized,
  next: NavigationGuardNext,
  userStore: ReturnType<typeof useUserStore>
): boolean {
  if (to.path !== '/') {
    return false
  }

  if (userStore.isLogin) {
    const menuStore = useMenuStore()
    const targetPath = menuStore.getHomePath() || RoutesAlias.Layout

    next({
      path: targetPath === '/' ? RoutesAlias.Layout : targetPath,
      replace: true
    })
    return true
  }

  next({
    name: 'Login',
    query: { redirect: DEFAULT_LOGIN_REDIRECT },
    replace: true
  })
  return true
}

function isStaticRoute(path: string): boolean {
  const checkRoute = (routes: any[], targetPath: string): boolean => {
    return routes.some((route) => {
      if (route.name === 'Exception404') {
        return false
      }

      const routePath = route.path
      const pattern = routePath.replace(/:[^/]+/g, '[^/]+').replace(/\*/g, '.*')
      const regex = new RegExp(`^${pattern}$`)

      if (regex.test(targetPath)) {
        return true
      }

      if (route.children && route.children.length > 0) {
        return checkRoute(route.children, targetPath)
      }

      return false
    })
  }

  return checkRoute(staticRoutes, path)
}

function isGuestDashboardEntry(path: string): boolean {
  return path === DEFAULT_LOGIN_REDIRECT || path.startsWith(`${DEFAULT_LOGIN_REDIRECT}/`)
}

async function handleDynamicRoutes(
  to: RouteLocationNormalized,
  next: NavigationGuardNext,
  router: Router
): Promise<void> {
  routeInitInProgress = true
  pendingLoading = true
  loadingService.showLoading()

  try {
    await fetchUserInfo()

    const menuList = await menuProcessor.getMenuList()
    if (!menuProcessor.validateMenuList(menuList)) {
      throw new Error('Failed to fetch menu list, please log in again.')
    }

    routeRegistry?.register(menuList)

    const menuStore = useMenuStore()
    menuStore.setMenuList(menuList)
    menuStore.addRemoveRouteFns(routeRegistry?.getRemoveRouteFns() || [])

    IframeRouteManager.getInstance().save()
    useWorktabStore().validateWorktabs(router)

    if (isStaticRoute(to.path)) {
      routeInitInProgress = false
      next({
        path: to.path,
        query: to.query,
        hash: to.hash,
        replace: true
      })
      return
    }

    const { homePath } = useCommon()
    const { path: validatedPath, hasPermission } = RoutePermissionValidator.validatePath(
      to.path,
      menuList,
      homePath.value || '/'
    )

    routeInitInProgress = false

    if (!hasPermission) {
      closeLoading()
      console.warn(`[RouteGuard] No permission for route ${to.path}, redirecting to ${validatedPath}`)
      next({
        path: validatedPath,
        replace: true
      })
      return
    }

    next({
      path: to.path,
      query: to.query,
      hash: to.hash,
      replace: true
    })
  } catch (error) {
    console.error('[RouteGuard] Failed to initialize dynamic routes:', error)
    closeLoading()

    if (isUnauthorizedError(error)) {
      routeInitInProgress = false
      next(false)
      return
    }

    routeInitFailed = true
    routeInitInProgress = false

    if (isHttpError(error)) {
      console.error(`[RouteGuard] HTTP ${error.code}: ${error.message}`)
    }

    next({ name: 'Exception500', replace: true })
  }
}

async function fetchUserInfo(): Promise<void> {
  const userStore = useUserStore()
  const data = await fetchGetUserInfo()
  userStore.setUserInfo(data)
  userStore.checkAndClearWorktabs()
}

export function resetRouterState(delay: number): void {
  setTimeout(() => {
    routeRegistry?.unregister()
    IframeRouteManager.getInstance().clear()

    const menuStore = useMenuStore()
    menuStore.removeAllDynamicRoutes()
    menuStore.setMenuList([])

    resetRouteInitState()
  }, delay)
}

function isUnauthorizedError(error: unknown): boolean {
  return isHttpError(error) && error.code === ApiStatus.unauthorized
}
