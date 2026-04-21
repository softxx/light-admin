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
  return request.get<Api.SystemManage.UserList>({
    url: '/user',
    params: normalizeListParams(params as Record<string, any>)
  })
}

export function fetchGetUserDetail(id: number | string) {
  return request.get<Api.SystemManage.UserListItem>({
    url: `/user/${id}/edit`
  })
}

export function fetchGetActiveUsers(params?: Record<string, any>) {
  return request.get<Api.Common.PaginatedResponse<any>>({
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

export function fetchDeleteUser(id: number | string) {
  return request.del<void>({
    url: `/user/${id}`,
    showSuccessMessage: true
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

export function fetchGetPublicSystemSetting() {
  return request.get<Api.SystemManage.SystemSettingItem>({
    url: '/system_setting/public'
  })
}

export function fetchGetSystemSetting() {
  return request.get<Api.SystemManage.SystemSettingItem>({
    url: '/system_setting'
  })
}

export function fetchUpdateSystemSetting(params: Api.SystemManage.SystemSettingPayload) {
  return request.post<Api.SystemManage.SystemSettingItem>({
    url: '/system_setting',
    params,
    showSuccessMessage: true
  })
}

export function fetchGetRoleList(params: Api.SystemManage.RoleSearchParams) {
  return request.get<Api.SystemManage.RoleList>({
    url: '/role',
    params: normalizeListParams(params as Record<string, any>)
  })
}

export function fetchGetRoleAll() {
  return request.get<Api.SystemManage.RoleOption[]>({
    url: '/role/all'
  })
}

export function fetchGetRoleDetail(id: number | string) {
  return request.get<Api.SystemManage.RoleListItem>({
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
  return request.get<Api.Backend.AuthAccessResponse>({
    url: '/authAccess',
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
  return request.get<Api.SystemManage.MenuListItem[]>({
    url: '/menu',
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
  return request.get<Api.SystemManage.DepartmentOption[]>({
    url: '/department',
    params
  })
}

export function fetchGetDepartmentDetail(id: number | string) {
  return request.get<Api.SystemManage.DepartmentOption>({
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
  return request.get<Api.SystemManage.DictListItem[]>({
    url: '/dict',
    params
  })
}

export function fetchGetDictOptions(type: string[] | string, str = false) {
  return request.get<Api.Common.DictOption[] | Record<string, Api.Common.DictOption[]>>({
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

export function fetchGetLoginLogList(params?: Record<string, any>) {
  return request.get<Api.SystemManage.LogList>({
    url: '/login_log',
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

export function fetchExportLoginLog() {
  return request.request<Blob>({
    url: '/login_log/export',
    method: 'POST',
    responseType: 'blob'
  })
}

export function fetchGetOperateLogList(params?: Record<string, any>) {
  return request.get<Api.SystemManage.LogList>({
    url: '/operate_log',
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

export function fetchGetGeneratorList(params?: Record<string, any>) {
  return request.get<Api.SystemManage.GeneratorList>({
    url: '/generator',
    params: normalizeListParams(params)
  })
}

export function fetchGetDatabaseTables(params?: Record<string, any>) {
  return request.get<any[]>({
    url: '/generator/getAllTable',
    params: normalizeListParams(params)
  })
}

export function fetchImportGeneratorTables(table: any[]) {
  return request.post<{ id: number }>({
    url: '/generator',
    params: { table },
    showSuccessMessage: true
  })
}

export function fetchGetGeneratorDetail(id: number | string) {
  return request.get<any>({
    url: `/generator/${id}/edit`
  })
}

export function fetchSaveGenerator(id: number | string, params: Record<string, any>) {
  return request.put<void>({
    url: `/generator/${id}`,
    params,
    showSuccessMessage: true
  })
}

export function fetchDeleteGenerator(id: number | string, options: RequestMessageOptions = {}) {
  return request.del<void>({
    url: `/generator/${id}`,
    showSuccessMessage: true,
    ...options
  })
}

export function fetchDeleteGeneratorField(id: number | string) {
  return request.del<void>({
    url: `/generator/deleteFiled/${id}`,
    showSuccessMessage: true
  })
}

export function fetchMakeGeneratorCode(id: number | string) {
  return request.post<any>({
    url: `/generator/makeCode/${id}`,
    showSuccessMessage: true
  })
}

export function fetchPreviewGeneratorCode(id: number | string) {
  return request.get<any>({
    url: `/generator/preview/${id}`
  })
}
