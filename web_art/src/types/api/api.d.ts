declare namespace Api {
  namespace Common {
    interface PaginationParams {
      current: number
      size: number
      total: number
    }

    type CommonSearchParams = Partial<Pick<PaginationParams, 'current' | 'size'>> & {
      page?: number
      pageSize?: number
    }

    interface PaginatedResponse<T = any> {
      data?: T[]
      list?: T[]
      records?: T[]
      items?: T[]
      total: number
      current?: number
      current_page?: number
      size?: number
      per_page?: number
    }

    type EnableStatus = 1 | 2 | '1' | '2'

    interface DictOption {
      label: string
      value: string | number
      [key: string]: any
    }

    interface UploadFileResponse {
      id: number
      url: string
      name: string
      path: string
      [key: string]: any
    }
  }

  namespace Auth {
    interface LoginParams {
      username?: string
      userName?: string
      password: string
    }

    interface LoginResponse {
      token: string
      refreshToken: string
      access_token: string
      refresh_token: string
      expiresIn: number
    }

    interface UserInfo {
      userId: number
      userName: string
      username: string
      realname: string
      email: string
      phone?: string
      avatar?: string
      department_name?: string
      role_name?: string[]
      roles: Array<number | string>
      rules: string[]
      buttons: string[]
      [key: string]: any
    }
  }

  namespace Backend {
    interface RouteMenu {
      id?: number
      path: string
      component: string
      title: string
      icon?: string
      hidden?: boolean | number
      type?: number
      hide_children?: boolean | number
      active_key?: string
      link_url?: string
      open_type?: number | string
      redirect?: string
      children?: RouteMenu[]
    }

    interface AuthTreeNode {
      id: number
      pid?: number
      title: string
      children?: AuthTreeNode[]
    }

    interface AuthAccessResponse {
      authNode: AuthTreeNode[]
      checked: number[]
    }
  }

  namespace SystemManage {
    interface RoleOption {
      id: number
      name: string
      role_key?: string
      [key: string]: any
    }

    interface DepartmentOption {
      id: number
      value: number | string
      title: string
      name: string
      parent_id?: number | string
      children?: DepartmentOption[]
      [key: string]: any
    }

    interface UserListItem {
      id: number
      username: string
      realname: string
      phone?: string
      email?: string
      dept_id?: number
      department_name?: string
      avatar?: string
      status: number | string
      is_admin?: number
      roles?: RoleOption[]
      create_time?: string
      [key: string]: any
    }

    type UserList = Api.Common.PaginatedResponse<UserListItem>

    type UserSearchParams = Partial<{
      key: string
      roles: number | string
      status: number | string
      dept_id: number | string
      create_time: string[] | [string, string]
      current: number
      size: number
      page: number
      pageSize: number
    }>

    interface UserPayload {
      id?: number
      username?: string
      realname: string
      phone?: string
      email?: string
      dept_id: number | string
      roles: Array<number | string>
      avatar?: string
    }

    interface RoleListItem {
      id: number
      name: string
      role_key: string
      note?: string
      data_range?: number | string
      departments?: Array<number | string>
      create_time?: string
      [key: string]: any
    }

    type RoleList = Api.Common.PaginatedResponse<RoleListItem>

    type RoleSearchParams = Partial<{
      key: string
      current: number
      size: number
      page: number
      pageSize: number
    }>

    interface RolePayload {
      id?: number
      name: string
      role_key: string
      note?: string
      data_range: number | string
      departments?: Array<number | string>
    }

    interface MenuListItem {
      id: number
      pid: number
      path: string
      component: string
      hidden: number | boolean
      title: string
      icon?: string
      rules?: string
      sort?: number
      type: number
      hide_children?: number | boolean
      active_key?: string
      open_type?: number | string
      link_url?: string
      children?: MenuListItem[]
      status?: string
      type_text?: string | Partial<DictListItem>
      [key: string]: any
    }

    interface MenuPayload {
      id?: number
      pid: number | string
      title: string
      path?: string
      component?: string
      icon?: string
      rules?: string
      sort?: number | string
      type: number | string
      hidden?: number | boolean
      hide_children?: number | boolean
      active_key?: string
      open_type?: number | string
      link_url?: string
    }

    interface DictListItem {
      id: number
      type: string
      name: string
      value: string | number
      sort?: number
      note?: string
      status: number | string
      color?: string
      widget_type?: string
      [key: string]: any
    }

    interface DictPayload {
      id?: number
      type: string
      name: string
      value: string | number
      sort?: number | string
      note?: string
      color?: string
      widget_type?: string
      status?: number | string
    }

    interface SystemSettingItem {
      system_name: string
      logo?: string
      favicon?: string
      homepage_enabled?: number | string | boolean
      homepage_title?: string
      homepage_intro?: string
    }

    interface SystemSettingPayload {
      system_name: string
      logo?: string
      favicon?: string
      homepage_enabled?: number | string | boolean
      homepage_title?: string
      homepage_intro?: string
    }

    interface LogListItem {
      id: number
      create_time?: string
      login_time?: string
      user?: {
        id: number
        realname?: string
        username?: string
      }
      [key: string]: any
    }

    type LogList = Api.Common.PaginatedResponse<LogListItem>

    interface GeneratorListItem {
      id: number
      table_name: string
      table_comment?: string
      module_name?: string
      menu_name?: string
      create_time?: string
      [key: string]: any
    }

    type GeneratorList = Api.Common.PaginatedResponse<GeneratorListItem>
  }
}
