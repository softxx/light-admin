import { AppRouteRecord } from '@/types/router'

export const systemRoutes: AppRouteRecord = {
  path: '/system',
  name: 'System',
  component: '/index/index',
  meta: {
    title: 'menus.system.title',
    icon: 'ri:user-3-line',
    roles: ['R_SUPER', 'R_ADMIN']
  },
  children: [
    {
      path: 'user',
      name: 'User',
      component: '/system/user',
      meta: {
        title: 'menus.system.user',
        keepAlive: true,
        roles: ['R_SUPER', 'R_ADMIN']
      }
    },
    {
      path: 'role',
      name: 'Role',
      component: '/system/role',
      meta: {
        title: 'menus.system.role',
        keepAlive: true,
        roles: ['R_SUPER']
      }
    },
    {
      path: 'user-center',
      name: 'UserCenter',
      component: '/system/user-center',
      meta: {
        title: 'menus.system.userCenter',
        isHide: true,
        keepAlive: true,
        isHideTab: true,
        activePath: '/system/user'
      }
    },
    {
      path: 'menu',
      name: 'Menus',
      component: '/system/menu',
      meta: {
        title: 'menus.system.menu',
        keepAlive: true,
        roles: ['R_SUPER'],
        authList: [
          { title: '新增', authMark: 'add' },
          { title: '编辑', authMark: 'edit' },
          { title: '删除', authMark: 'delete' }
        ]
      }
    },
    {
      path: 'file',
      name: 'FileManage',
      component: '/system/file-manage',
      meta: {
        // 后端菜单模式下由 light_menu 控制入口，这里用于前端模式兜底。
        title: '文件管理',
        keepAlive: true,
        roles: ['R_SUPER', 'R_ADMIN']
      }
    },
    {
      path: 'version',
      name: 'VersionManage',
      component: '/system/version-manage',
      meta: {
        title: '版本管理',
        keepAlive: true,
        roles: ['R_SUPER']
      }
    }
  ]
}
