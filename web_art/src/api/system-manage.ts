import request from '@/utils/http'
import { normalizePaginationRequestParams } from '@/utils/table/tableUtils'

interface RequestMessageOptions {
  showSuccessMessage?: boolean
  showErrorMessage?: boolean
}

function normalizeListParams(params?: Record<string, any>) {
  if (!params) {
    return params
  }

  return normalizePaginationRequestParams(params)
}

export function fetchGetUserList(params: Api.SystemManage.UserSearchParams) {
  return request.post<Api.SystemManage.UserList>({
    url: '/user/list',
    params: normalizeListParams(params as Record<string, any>)
  })
}

export function fetchGetUserDetail(id: number | string) {
  return request.post<Api.SystemManage.UserListItem>({
    url: `/user/${id}/edit`
  })
}

export function fetchGetActiveUsers(params?: Record<string, any>) {
  return request.post<Api.Common.PaginatedResponse<any>>({
    url: '/user/getActiveUsers',
    params: normalizeListParams(params)
  })
}

export function fetchSaveUser(params: Api.SystemManage.UserPayload) {
  return request.request<void>({
    url: params.id ? `/user/${params.id}` : '/user',
    method: params.id ? 'PUT' : 'POST',
    params,
    showSuccessMessage: true
  })
}

export function fetchDeleteUser(id: number | string, options: RequestMessageOptions = {}) {
  return request.del<void>({
    url: `/user/${id}`,
    showSuccessMessage: true,
    ...options
  })
}

export function fetchChangeUserStatus(id: number | string) {
  return request.put<void>({
    url: `/user/changeStatus/${id}`,
    showSuccessMessage: true
  })
}

export function fetchResetUserPassword(id: number | string) {
  return request.put<{ password: string }>({
    url: `/user/resetPassword/${id}`,
    showSuccessMessage: true
  })
}

export function fetchUpdateUserInfo(params: Record<string, any>) {
  return request.put<void>({
    url: '/user/updateInfo',
    params,
    showSuccessMessage: true
  })
}

export function fetchChangePassword(params: Record<string, any>) {
  return request.put<void>({
    url: '/user/changePassword',
    params,
    showSuccessMessage: true
  })
}

export function fetchUploadImage(data: FormData) {
  return request.post<Api.Common.UploadFileResponse>({
    url: '/upload/image',
    data
  })
}

export function fetchUploadFile(data: FormData) {
  return request.post<Api.Common.UploadFileResponse>({
    url: '/upload/file',
    data
  })
}

export function fetchUploadAttachment(data: FormData) {
  return request.post<Api.Common.UploadFileResponse>({
    url: '/upload/attachment',
    data
  })
}

// 文件管理列表，复用后台通用分页和动态筛选参数。
export function fetchGetFileList(params?: Api.SystemManage.FileSearchParams) {
  return request.post<Api.SystemManage.FileList>({
    url: '/file/list',
    params: normalizeListParams(params as Record<string, any>)
  })
}

// 删除文件记录，同时后端会尝试删除本地物理文件。
export function fetchDeleteFile(id: number | string, options: RequestMessageOptions = {}) {
  return request.post<void>({
    url: '/file/delete',
    params: { id },
    showSuccessMessage: true,
    ...options
  })
}

export function fetchGetPublicSystemSetting() {
  return request.get<Api.SystemManage.SystemSettingItem>({
    url: '/system_setting/public'
  })
}

export function fetchGetSystemSetting() {
  return request.post<Api.SystemManage.SystemSettingItem>({
    url: '/system_setting/query'
  })
}

export function fetchUpdateSystemSetting(params: Api.SystemManage.SystemSettingPayload) {
  return request.post<Api.SystemManage.SystemSettingItem>({
    url: '/system_setting',
    params,
    showSuccessMessage: true
  })
}

export function fetchGetCacheOverview() {
  return request.post<Api.SystemManage.CacheOverview>({
    url: '/cache/overview'
  })
}

export function fetchRefreshDictCache() {
  return request.post<Api.SystemManage.DictCacheRefreshResult>({
    url: '/cache/refreshDict',
    showSuccessMessage: true
  })
}

export function fetchClearRuntimeCache() {
  return request.post<Api.SystemManage.RuntimeCacheClearResult>({
    url: '/cache/clearRuntime',
    showSuccessMessage: true
  })
}

export function fetchSaveCacheSetting(params: Api.SystemManage.CacheSettingPayload) {
  return request.post<Api.SystemManage.CacheSetting>({
    url: '/cache/saveSetting',
    params,
    showSuccessMessage: true
  })
}

// 版本管理中心：基于可切换发布源检查、下载、预检、升级、回滚和任务轮询。
type VersionReleaseRequestParams = {
  source?: 'github' | 'gitlab' | 'gitee' | 'cnb' | string
  owner?: string
  repo?: string
  project?: string
  asset_pattern?: string
  include_prerelease?: boolean
  version?: string
  package_path?: string
}

export function fetchGetVersionCurrent() {
  return request.post<Api.SystemManage.VersionCurrentResponse>({
    url: '/version/current'
  })
}

export function fetchCheckVersion(params: VersionReleaseRequestParams) {
  return request.post<Api.SystemManage.VersionCheckResponse>({
    url: '/version/check',
    params
  })
}

export function fetchDownloadVersionPackage(params: VersionReleaseRequestParams) {
  return request.post<Api.SystemManage.VersionDownloadResponse>({
    url: '/version/download',
    params,
    showSuccessMessage: true
  })
}

export function fetchPrecheckVersion(params: VersionReleaseRequestParams) {
  return request.post<Api.SystemManage.VersionPrecheckResponse>({
    url: '/version/precheck',
    params
  })
}

export function fetchStartVersionUpgrade(params: VersionReleaseRequestParams) {
  return request.post<Api.SystemManage.UpgradeTask>({
    url: '/version/upgrade',
    params,
    showSuccessMessage: true
  })
}

export function fetchRollbackVersionTask(taskId?: number | string) {
  return request.post<Api.SystemManage.UpgradeTask>({
    url: '/version/rollback',
    params: taskId ? { task_id: taskId } : {},
    showSuccessMessage: true
  })
}

export function fetchGetVersionTask(id: number | string) {
  return request.post<Api.SystemManage.UpgradeTask>({
    url: '/version/task',
    params: { id }
  })
}

export function fetchGetVersionTasks(limit = 20) {
  return request.post<Api.SystemManage.UpgradeTask[]>({
    url: '/version/tasks',
    params: { limit }
  })
}

export function fetchGetRoleList(params: Api.SystemManage.RoleSearchParams) {
  return request.post<Api.SystemManage.RoleList>({
    url: '/role/list',
    params: normalizeListParams(params as Record<string, any>)
  })
}

export function fetchGetRoleAll() {
  return request.post<Api.SystemManage.RoleOption[]>({
    url: '/role/all'
  })
}

export function fetchGetRoleDetail(id: number | string) {
  return request.post<Api.SystemManage.RoleListItem>({
    url: `/role/${id}/edit`
  })
}

export function fetchSaveRole(params: Api.SystemManage.RolePayload) {
  return request.request<void>({
    url: params.id ? `/role/${params.id}` : '/role',
    method: params.id ? 'PUT' : 'POST',
    params,
    showSuccessMessage: true
  })
}

export function fetchDeleteRole(id: number | string, options: RequestMessageOptions = {}) {
  return request.del<void>({
    url: `/role/${id}`,
    showSuccessMessage: true,
    ...options
  })
}

export function fetchGetRolePermission(id: number | string) {
  return request.post<Api.Backend.AuthAccessResponse>({
    url: '/authAccess/index',
    params: { id }
  })
}

export function fetchSaveRolePermission(roleId: number | string, menuIds: Array<number | string>) {
  return request.post<void>({
    url: '/authAccess',
    params: {
      role_id: roleId,
      menu_id: menuIds
    },
    showSuccessMessage: true
  })
}

export function fetchGetMenuList(params?: Record<string, any>) {
  return request.post<Api.SystemManage.MenuListItem[]>({
    url: '/menu/list',
    params
  })
}

export function fetchSaveMenu(params: Api.SystemManage.MenuPayload) {
  return request.request<void>({
    url: params.id ? `/menu/${params.id}` : '/menu',
    method: params.id ? 'PUT' : 'POST',
    params,
    showSuccessMessage: true
  })
}

export function fetchDeleteMenu(id: number | string, options: RequestMessageOptions = {}) {
  return request.del<void>({
    url: `/menu/${id}`,
    showSuccessMessage: true,
    ...options
  })
}

export function fetchGetDepartmentList(params?: Record<string, any>) {
  return request.post<Api.SystemManage.DepartmentOption[]>({
    url: '/department/list',
    params
  })
}

export function fetchGetDepartmentDetail(id: number | string) {
  return request.post<Api.SystemManage.DepartmentOption>({
    url: `/department/${id}/edit`
  })
}

export function fetchSaveDepartment(params: Record<string, any>) {
  return request.request<void>({
    url: params.id ? `/department/${params.id}` : '/department',
    method: params.id ? 'PUT' : 'POST',
    params,
    showSuccessMessage: true
  })
}

export function fetchDeleteDepartment(id: number | string, options: RequestMessageOptions = {}) {
  return request.del<void>({
    url: `/department/${id}`,
    showSuccessMessage: true,
    ...options
  })
}

export function fetchGetDictList(params?: Record<string, any>) {
  return request.post<Api.SystemManage.DictListItem[]>({
    url: '/dict/list',
    params
  })
}

export function fetchGetDictOptions(type: string[] | string, str = false) {
  return request.post<Api.Common.DictOption[] | Record<string, Api.Common.DictOption[]>>({
    url: '/dict/get',
    params: {
      type,
      str: str ? 1 : 0
    }
  })
}

export function fetchSaveDict(params: Api.SystemManage.DictPayload) {
  return request.request<void>({
    url: params.id ? `/dict/${params.id}` : '/dict',
    method: params.id ? 'PUT' : 'POST',
    params,
    showSuccessMessage: true
  })
}

export function fetchDeleteDict(id: number | string, options: RequestMessageOptions = {}) {
  return request.del<void>({
    url: `/dict/${id}`,
    showSuccessMessage: true,
    ...options
  })
}

export function fetchChangeDictStatus(id: number | string) {
  return request.post<void>({
    url: `/dict/changeStatus/${id}`,
    showSuccessMessage: true
  })
}

export function fetchUpdateDictCache() {
  return request.post<void>({
    url: '/dict/updateCache',
    showSuccessMessage: true
  })
}

export function fetchUpdateDictSort(params: Array<{ id: number | string; sort: number }>) {
  return request.post<void>({
    url: '/dict/updateSort',
    params,
    showSuccessMessage: true
  })
}

export function fetchGetLoginLogList(params?: Api.SystemManage.LogSearchParams) {
  return request.post<Api.SystemManage.LogList>({
    url: '/login_log/list',
    params: normalizeListParams(params)
  })
}

export function fetchDeleteLoginLog(id: number | string, options: RequestMessageOptions = {}) {
  return request.post<void>({
    url: '/login_log/delete',
    params: { id },
    showSuccessMessage: true,
    ...options
  })
}

export function fetchClearLoginLog() {
  return request.del<void>({
    url: '/login_log/clear',
    showSuccessMessage: true
  })
}

export function fetchExportLoginLog(params?: Api.SystemManage.LogSearchParams) {
  return request.request<Blob>({
    url: '/login_log/export',
    method: 'POST',
    params: normalizeListParams(params),
    responseType: 'blob'
  })
}

export function fetchGetOperateLogList(params?: Record<string, any>) {
  return request.post<Api.SystemManage.LogList>({
    url: '/operate_log/list',
    params: normalizeListParams(params)
  })
}

export function fetchDeleteOperateLog(id: number | string, options: RequestMessageOptions = {}) {
  return request.post<void>({
    url: '/operate_log/delete',
    params: { id },
    showSuccessMessage: true,
    ...options
  })
}

export function fetchClearOperateLog() {
  return request.del<void>({
    url: '/operate_log/clear',
    showSuccessMessage: true
  })
}
