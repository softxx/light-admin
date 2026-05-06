import type { TableFilterFieldSchema } from '@/types'

export function createUserFilterFields(): TableFilterFieldSchema[] {
  // 部门/角色筛选已移除，权限现在直接维护在管理员账号上。
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
      label: '管理员状态',
      value: 'status',
      type: 'select',
      operators: ['eq', 'neq'],
      options: [
        { label: '启用', value: 1 },
        { label: '禁用', value: 2 }
      ],
      placeholder: '请选择管理员状态'
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
