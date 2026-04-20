import request from '@/utils/http'
import type { AppRouteRecord } from '@/types/router'

const iconPrefix = 'ant-design:'

const componentAliasMap: Record<string, string> = {
  Layout: '/index/index',
  Iframe: '',
  RouteView: '',
  'home/index': '/dashboard/console',
  'user/center/index': '/system/user-center',
  'system/user/index': '/system/user',
  'system/role/index': '/system/role',
  'system/role/auth': '/system/role/auth',
  'system/menu/index': '/system/menu',
  'system/department/index': '/system/department',
  'system/dict/index': '/system/dict',
  'system/logs/login-log': '/system/logs/login-log',
  'system/logs/operate-log': '/system/logs/operate-log',
  'system/generator/index': '/system/generator',
  'system/generator/edit': '/system/generator/edit'
}

function withLeadingSlash(path = '') {
  if (!path) return ''
  if (path.startsWith('/') || path.startsWith('http://') || path.startsWith('https://')) {
    return path
  }
  return `/${path}`
}

function normalizeIcon(icon?: string) {
  if (!icon) return 'ri:menu-line'
  if (icon.includes(':')) return icon
  return `${iconPrefix}${icon}`
}

function normalizeComponentPath(component?: string) {
  if (!component) return ''
  if (Object.prototype.hasOwnProperty.call(componentAliasMap, component)) {
    return componentAliasMap[component]
  }
  return withLeadingSlash(component)
}

function resolveMenuComponentPath(
  menu: Api.Backend.RouteMenu & { open_type?: number | string },
  parentPath = '',
  isIframe = false,
  isExternalLink = false
) {
  const hasChildren = Array.isArray(menu.children) && menu.children.length > 0
  const isNestedCatalogue = Boolean(parentPath) && hasChildren

  if (isIframe || isExternalLink) {
    return ''
  }

  if (isNestedCatalogue && menu.component === 'Layout') {
    return ''
  }

  return normalizeComponentPath(menu.component)
}

function createRouteName(menu: Api.Backend.RouteMenu, parentPath = '') {
  if (menu.id) {
    return `menu_${menu.id}`
  }

  const normalizedPath = `${parentPath}_${menu.path}`.replace(/[^\w]/g, '_')
  return normalizedPath.replace(/^_+/, '') || `menu_${Date.now()}`
}

function transformRouteMenu(
  menu: Api.Backend.RouteMenu & { open_type?: number | string },
  parentPath = ''
): AppRouteRecord {
  const openType = Number(menu.open_type ?? 0)
  const isExternalLink = openType === 2 || /^https?:\/\//.test(menu.path)
  const isIframe = menu.component === 'Iframe' || openType === 1

  return {
    id: menu.id,
    path: menu.path,
    name: createRouteName(menu, parentPath),
    component: resolveMenuComponentPath(menu, parentPath, isIframe, isExternalLink),
    redirect: menu.redirect,
    children: menu.children?.map((item) =>
      transformRouteMenu(item, `${parentPath}/${menu.path}`)
    ),
    meta: {
      title: menu.title,
      icon: normalizeIcon(menu.icon),
      isHide: Boolean(Number(menu.hidden ?? 0)),
      isIframe,
      link: isIframe ? menu.link_url || '' : isExternalLink ? menu.link_url || menu.path : undefined,
      activePath: withLeadingSlash(menu.active_key)
    }
  }
}

function normalizeUserInfo(userInfo: Record<string, any>): Api.Auth.UserInfo {
  const buttons = Array.isArray(userInfo?.rules) ? userInfo.rules : []

  return {
    ...userInfo,
    userId: Number(userInfo?.id || 0),
    userName: userInfo?.realname || userInfo?.username || '',
    username: userInfo?.username || '',
    realname: userInfo?.realname || '',
    email: userInfo?.email || '',
    avatar: userInfo?.avatar || '',
    roles: Array.isArray(userInfo?.roles) ? userInfo.roles : [],
    role_name: Array.isArray(userInfo?.role_name) ? userInfo.role_name : [],
    rules: buttons,
    buttons
  }
}

export function fetchLogin(params: Api.Auth.LoginParams) {
  return request.post<Api.Auth.LoginResponse>({
    url: '/login',
    params: {
      username: params.username || params.userName || '',
      password: params.password
    },
    skipAuthRefresh: true,
    responseAdapter: (data: any) => ({
      token: data.access_token,
      refreshToken: data.refresh_token,
      access_token: data.access_token,
      refresh_token: data.refresh_token,
      expiresIn: data.expires_in
    })
  })
}

export function fetchLogout(refreshToken: string) {
  return request.post<void>({
    url: '/logout',
    params: { refreshToken },
    showErrorMessage: false,
    skipAuthRefresh: true
  })
}

export function fetchGetUserInfo() {
  return request.get<Api.Auth.UserInfo>({
    url: '/getUserInfo',
    responseAdapter: normalizeUserInfo
  })
}

export function fetchGetMenuRoutes() {
  return request.get<AppRouteRecord[]>({
    url: '/getRouter',
    responseAdapter: (data: Api.Backend.RouteMenu[]) => data.map((item) => transformRouteMenu(item))
  })
}
