import type { TableFilterFieldSchema, TableFilterOption } from '@/types'

function mapDepartmentOptions(
  items: Api.SystemManage.DepartmentOption[] = []
): TableFilterOption[] {
  return items.map((item) => ({
    label: item.name,
    value: item.id,
    children: mapDepartmentOptions((item.children as Api.SystemManage.DepartmentOption[]) || [])
  }))
}

/**
 * Shared user filter schema.
 *
 * The list page and the search component both consume this schema so the
 * rendered controls and the serialized filter payload stay aligned.
 */
export function createUserFilterFields(
  roleOptions: Api.SystemManage.RoleOption[] = [],
  departmentOptions: Api.SystemManage.DepartmentOption[] = []
): TableFilterFieldSchema[] {
  return [
    {
      label: '用户名',
      value: 'username',
      type: 'text',
      placeholder: '请输入用户名'
    },
    {
      label: '姓名',
      value: 'realname',
      type: 'text',
      placeholder: '请输入姓名'
    },
    {
      label: '手机号',
      value: 'phone',
      type: 'text',
      placeholder: '请输入手机号'
    },
    {
      label: '角色',
      value: 'roles',
      type: 'select',
      operators: ['eq', 'neq'],
      options: roleOptions.map((item) => ({
        label: item.name,
        value: item.id
      })),
      placeholder: '请选择角色'
    },
    {
      label: '用户状态',
      value: 'status',
      type: 'select',
      operators: ['eq', 'neq'],
      options: [
        { label: '激活', value: 1 },
        { label: '禁用', value: 2 }
      ],
      placeholder: '请选择用户状态'
    },
    {
      label: '部门',
      value: 'dept_id',
      type: 'select',
      component: 'tree-select',
      operators: ['eq', 'neq'],
      options: mapDepartmentOptions(departmentOptions),
      placeholder: '请选择部门'
    },
    {
      label: '创建时间',
      value: 'create_time',
      type: 'date',
      dateType: 'date',
      placeholder: '请选择创建时间'
    }
  ]
}
