import type { TableFilterFieldSchema } from '@/types'

/**
 * Shared role filter schema.
 *
 * Keeping the field definition in one place reduces drift between the search
 * UI and the page-level request adapter.
 */
export function createRoleFilterFields(): TableFilterFieldSchema[] {
  return [
    {
      label: '角色名称',
      value: 'name',
      type: 'text',
      placeholder: '请输入角色名称'
    },
    {
      label: '权限标识',
      value: 'role_key',
      type: 'text',
      placeholder: '请输入权限标识'
    },
    {
      label: '备注',
      value: 'note',
      type: 'text',
      placeholder: '请输入备注'
    },
    {
      label: '数据范围',
      value: 'data_range',
      type: 'select',
      operators: ['eq', 'neq'],
      options: [
        { label: '全部数据', value: 1 },
        { label: '自定义数据', value: 2 },
        { label: '本人数据', value: 3 },
        { label: '部门数据', value: 4 },
        { label: '部门及以下数据', value: 5 }
      ],
      placeholder: '请选择数据范围'
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
