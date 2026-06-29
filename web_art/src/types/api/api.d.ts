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
      captchaId?: string
      captchaCode?: string
    }

    interface LoginResponse {
      token: string
      refreshToken: string
      access_token: string
      refresh_token: string
      expiresIn: number
    }

    interface LoginCaptchaPayload {
      captchaId: string
      image: string
      expireIn: number
    }

    interface LoginCaptchaMeta {
      enabled: boolean
      mode: 'always' | 'adaptive'
      requiredAfterAttempts: number
    }

    interface LoginCaptchaBootstrapPayload {
      meta: LoginCaptchaMeta
      captcha?: LoginCaptchaPayload
    }

    interface UserInfo {
      userId: number
      userName: string
      username: string
      realname: string
      email: string
      phone?: string
      avatar?: string
      is_super_admin?: boolean
      // Direct button permissions returned by the backend for the current user.
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
      // Permission tree plus the checked leaf nodes used by the user permission dialog.
      authNode: AuthTreeNode[]
      checked: number[]
    }
  }

  namespace SystemManage {
    interface UserListItem {
      id: number
      username: string
      realname: string
      phone?: string
      email?: string
      avatar?: string
      status: number | string
      is_admin?: number
      // Department and role fields are intentionally absent in the no-department version.
      create_time?: string
      [key: string]: any
    }

    type UserList = Api.Common.PaginatedResponse<UserListItem>

    type DynamicFilterSearchParams = Partial<{
      quick_filter: string
      filters: string
    }>

    type UserSearchParams = DynamicFilterSearchParams &
      Partial<{
        key: string
        status: number | string
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
      avatar?: string
      menu_id?: Array<number | string>
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
    }

    interface SystemSettingPayload {
      system_name: string
      logo?: string
      favicon?: string
    }

    interface FileListItem {
      id: number
      url: string
      mime_type: string
      file_ext: string
      file_size: number
      filename: string
      create_time?: string
      user_id?: number | string
      realname?: string
      username?: string
      user?: {
        id: number
        realname?: string
        username?: string
      }
      [key: string]: any
    }

    type FileList = Api.Common.PaginatedResponse<FileListItem>

    // 文件管理页查询参数，quick_filter / filters 由表格筛选组件序列化。
    type FileSearchParams = DynamicFilterSearchParams &
      Partial<{
        filename: string
        file_ext: string
        mime_type: string
        file_size: number
        user_id: number | string
        create_time: string[] | [string, string]
        current: number
        size: number
        page: number
        pageSize: number
      }>

    interface CacheOverview {
      browser: {
        scope: string
      }
      dict: {
        type_count: number
        cached_count: number
      }
      runtime: {
        supported: boolean
        driver: string
        path: string
        file_count: number
        size_bytes: number
        protected_file_count: number
      }
      setting: CacheSetting
    }

    interface CacheRedisSetting {
      host: string
      port: number
      password: string
      password_set: boolean
      clear_password: boolean
      select: number
      timeout: number
      persistent: boolean
      prefix: string
      expire: number
    }

    interface CacheSetting {
      driver: 'file' | 'redis' | string
      drivers: string[]
      redis: CacheRedisSetting
      health: {
        available: boolean
        message: string
      }
    }

    interface CacheSettingPayload {
      driver: 'file' | 'redis' | string
      redis: Partial<CacheRedisSetting>
    }

    interface DictCacheRefreshResult {
      type_count: number
      cached_count: number
      refreshed_count: number
    }

    interface RuntimeCacheClearResult {
      removed_count: number
      removed_size_bytes: number
      skipped_count: number
      failed_count: number
      remaining_file_count: number
      remaining_size_bytes: number
      protected_file_count: number
    }

    interface VersionInfo {
      version: string
      build?: string
      commit?: string
      released_at?: string
      channel?: string
      [key: string]: any
    }

    interface VersionReleaseConfig {
      app: string
      channel: string
      source: 'github' | 'gitlab' | 'gitee' | 'cnb' | string
      has_release_token: boolean
      owner: string
      repo: string
      project: string
      asset_pattern: string
      api_base?: string
      include_prerelease: boolean
    }

    interface VersionEnvironment {
      php_version: string
      thinkphp_version: string
      root_path: string
      runtime_path: string
      os: string
    }

    interface VersionReleaseItem {
      version: string
      tag_name?: string
      build?: string
      commit?: string
      released_at?: string
      channel?: string
      source?: string
      required?: boolean
      min_upgradable_version?: string
      php?: string
      database_migration?: boolean
      package_url?: string
      asset_api_url?: string
      asset_name?: string
      release_url?: string
      digest?: string
      size_bytes?: number
      sha256?: string
      release_notes?: string[]
      [key: string]: any
    }

    interface VersionCurrentResponse {
      current: VersionInfo
      release: VersionReleaseConfig
      installed?: Record<string, any>
      last_task?: UpgradeTask
      environment: VersionEnvironment
    }

    interface VersionCheckResponse {
      current: VersionInfo
      source_url: string
      manifest: {
        app: string
        channel: string
        latest: string
        source?: string
        platform?: Record<string, any>
      }
      latest?: VersionReleaseItem
      upgrade_available: boolean
    }

    interface VersionPrecheckItem {
      key: string
      title: string
      status: 'pass' | 'warn' | 'fail'
      message: string
    }

    interface VersionPrecheckResponse {
      can_upgrade: boolean
      failed_count: number
      warning_count: number
      checks: VersionPrecheckItem[]
      source_url: string
      version: VersionReleaseItem
    }

    interface VersionDownloadResponse {
      package_path: string
      size_bytes: number
      sha256: string
      verification: {
        sha256: string
        remote_sha256: string
        digest_checked: boolean
        sha256_matched: boolean
      }
      source_url: string
      version: VersionReleaseItem
    }

    type UpgradeTaskStatus =
      | 'pending'
      | 'queued'
      | 'downloading'
      | 'verifying'
      | 'prechecking'
      | 'backing_up'
      | 'maintenance'
      | 'installing'
      | 'migrating'
      | 'finishing'
      | 'success'
      | 'failed'
      | 'rolling_back'
      | 'rolled_back'
      | 'rollback_failed'

    interface UpgradeTaskLog {
      time: string
      level: 'info' | 'warn' | 'error' | string
      message: string
    }

    interface UpgradeTask {
      id: number
      target_version: string
      package_url?: string
      package_path?: string
      backup_path?: string
      manifest_url?: string
      manifest?: VersionReleaseItem
      precheck?: VersionPrecheckResponse | Record<string, any>
      status: UpgradeTaskStatus
      progress: number
      message: string
      logs: UpgradeTaskLog[]
      error?: string
      operator_id?: number
      started_at?: number | string
      finished_at?: number | string
      create_time?: number | string
      update_time?: number | string
      is_running?: boolean
      [key: string]: any
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

    type LogSearchParams = DynamicFilterSearchParams &
      Partial<{
        account: string
        realname: string
        login_ip: string
        login_time: string[] | [string, string]
        current: number
        size: number
        page: number
        pageSize: number
      }>
  }
}
