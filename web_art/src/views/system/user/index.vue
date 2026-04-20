<template>
  <div class="user-page art-full-height">
    <UserSearch
      v-model="searchForm"
      :role-options="roleOptions"
      :department-options="departmentOptions"
      @search="handleSearch"
      @reset="handleReset"
    />

    <ElCard class="art-table-card" style="margin-top: 12px">
      <ArtTableHeader v-model:columns="columnChecks" :loading="loading" @refresh="refreshData">
        <template #left>
          <ElButton v-auth="'system:user:save'" @click="openDialog('add')" v-ripple>
            新增用户
          </ElButton>
        </template>
      </ArtTableHeader>

      <ArtTable
        :loading="loading"
        :data="data"
        :columns="columns"
        :pagination="pagination"
        @pagination:size-change="handleSizeChange"
        @pagination:current-change="handleCurrentChange"
      />

      <UserDialog
        v-model:visible="dialogVisible"
        :type="dialogType"
        :user-data="currentUserData"
        :role-options="roleOptions"
        :department-options="departmentOptions"
        @success="handleDialogSuccess"
      />
    </ElCard>
  </div>
</template>

<script setup lang="ts">
  import ArtButtonTable from '@/components/core/forms/art-button-table/index.vue'
  import { useAuth } from '@/hooks'
  import { useTable } from '@/hooks/core/useTable'
  import {
    fetchChangeUserStatus,
    fetchDeleteUser,
    fetchGetDepartmentList,
    fetchGetRoleAll,
    fetchGetUserList,
    fetchResetUserPassword
  } from '@/api/system-manage'
  import UserDialog from './modules/user-dialog.vue'
  import UserSearch from './modules/user-search.vue'
  import { ElAvatar, ElMessageBox, ElSwitch, ElTag } from 'element-plus'

  defineOptions({ name: 'User' })

  type DialogType = 'add' | 'edit'
  type UserListItem = Api.SystemManage.UserListItem

  const { hasAuth } = useAuth()

  const roleOptions = ref<Api.SystemManage.RoleOption[]>([])
  const departmentOptions = ref<Api.SystemManage.DepartmentOption[]>([])
  const dialogVisible = ref(false)
  const dialogType = ref<DialogType>('add')
  const currentUserData = ref<Partial<UserListItem>>({})

  const searchForm = ref<Api.SystemManage.UserSearchParams>({
    key: undefined,
    roles: undefined,
    status: undefined,
    dept_id: undefined,
    create_time: undefined
  })

  const {
    columns,
    columnChecks,
    data,
    loading,
    pagination,
    getData,
    replaceSearchParams,
    resetSearchParams,
    handleSizeChange,
    handleCurrentChange,
    refreshData,
    refreshCreate,
    refreshRemove,
    refreshUpdate
  } = useTable({
    core: {
      apiFn: fetchGetUserList,
      apiParams: {
        page: 1,
        pageSize: 20,
        ...searchForm.value
      },
      columnsFactory: () => [
        {
          prop: 'username',
          label: '用户信息',
          minWidth: 220,
          formatter: (row: UserListItem) =>
            h('div', { class: 'flex items-center gap-3' }, [
              h(
                ElAvatar,
                {
                  src: row.avatar || '',
                  size: 36
                },
                () => row.realname?.slice(0, 1) || row.username?.slice(0, 1) || 'U'
              ),
              h('div', [
                h('div', { class: 'font-medium text-[var(--art-gray-900)]' }, row.username || '-'),
                h('div', { class: 'text-xs text-[var(--art-gray-500)] mt-1' }, row.realname || '-')
              ])
            ])
        },
        {
          prop: 'department_name',
          label: '部门',
          minWidth: 120,
          formatter: (row: UserListItem) => row.department_name || '-'
        },
        {
          prop: 'roles',
          label: '角色',
          minWidth: 180,
          formatter: (row: UserListItem) => {
            const roles = Array.isArray(row.roles) ? row.roles : []
            if (!roles.length) {
              return '-'
            }

            return h(
              'div',
              roles.map((item) =>
                h(
                  ElTag,
                  {
                    class: 'mr-2',
                    type: 'primary'
                  },
                  () => item.name
                )
              )
            )
          }
        },
        {
          prop: 'phone',
          label: '手机号',
          minWidth: 130,
          formatter: (row: UserListItem) => row.phone || '-'
        },
        {
          prop: 'status',
          label: '状态',
          width: 120,
          formatter: (row: UserListItem) =>
            h(ElSwitch, {
              disabled: Number(row.is_admin || 0) === 1,
              modelValue: String(row.status) === '1',
              activeText: '激活',
              inactiveText: '禁用',
              inlinePrompt: true,
              onChange: () => handleChangeStatus(row)
            })
        },
        {
          prop: 'create_time',
          label: '创建时间',
          minWidth: 120
        },
        {
          prop: 'operation',
          label: '操作',
          width: 180,
          fixed: 'right',
          formatter: (row: UserListItem) => {
            const buttons = []
            const isAdminAccount =
              Number(row.is_admin || 0) === 1 || String(row.username).toLowerCase() === 'admin'

            if (hasAuth('system:user:update') && !isAdminAccount) {
              buttons.push(
                h(ArtButtonTable, {
                  type: 'edit',
                  onClick: () => openDialog('edit', row)
                })
              )
            }

            if (hasAuth('system:user:resetPassword') && !isAdminAccount) {
              buttons.push(
                h(ArtButtonTable, {
                  icon: 'ri:lock-password-line',
                  iconClass: 'bg-warning/12 text-warning',
                  tooltip: '重置密码',
                  onClick: () => handleResetPassword(row)
                })
              )
            }

            if (hasAuth('system:user:delete') && !isAdminAccount) {
              buttons.push(
                h(ArtButtonTable, {
                  type: 'delete',
                  onClick: () => handleDelete(row)
                })
              )
            }

            return h('div', buttons)
          }
        }
      ]
    }
  })

  const openDialog = (type: DialogType, row?: UserListItem) => {
    dialogType.value = type
    currentUserData.value = row || {}
    dialogVisible.value = true
  }

  const handleSearch = (params: Api.SystemManage.UserSearchParams) => {
    replaceSearchParams(params)
    getData()
  }

  const handleReset = async () => {
    await resetSearchParams()
  }

  const handleChangeStatus = async (row: UserListItem) => {
    await fetchChangeUserStatus(row.id)
    await refreshData()
  }

  const handleResetPassword = async (row: UserListItem) => {
    await ElMessageBox.confirm(
      `确定重置用户“${row.realname || row.username}”的密码吗？`,
      '重置密码',
      {
        type: 'warning',
        confirmButtonText: '确定',
        cancelButtonText: '取消'
      }
    )

    const result = await fetchResetUserPassword(row.id)
    await ElMessageBox.alert(`新密码：${result.password}`, '重置成功', {
      confirmButtonText: '知道了'
    })
  }

  const handleDelete = async (row: UserListItem) => {
    await ElMessageBox.confirm(`确定删除用户“${row.realname || row.username}”吗？`, '删除确认', {
      type: 'warning',
      confirmButtonText: '确定',
      cancelButtonText: '取消'
    })

    await fetchDeleteUser(row.id)
    await refreshRemove()
  }

  const handleDialogSuccess = async (type: DialogType) => {
    if (type === 'add') {
      await refreshCreate()
      return
    }

    await refreshUpdate()
  }

  onMounted(async () => {
    const [roles, departments] = await Promise.all([fetchGetRoleAll(), fetchGetDepartmentList()])
    roleOptions.value = roles
    departmentOptions.value = departments
  })
</script>
