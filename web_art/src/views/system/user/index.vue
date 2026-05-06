<template>
  <div class="user-page art-full-height">
    <UserSearch v-model="searchForm" @search="handleSearch" @reset="handleReset" />

    <ElCard class="art-table-card" style="margin-top: 12px">
      <ArtTableHeader v-model:columns="columnChecks" :loading="loading" @refresh="refreshData">
        <template #left>
          <ElButton v-auth="'system:user:save'" @click="openDialog('add')" v-ripple>
            新增管理员
          </ElButton>
          <ElButton
            v-if="canDeleteUser"
            type="danger"
            :disabled="!hasSelection"
            :loading="batchDeleting"
            @click="handleBatchDelete"
            v-ripple
          >
            批量删除
          </ElButton>
        </template>
      </ArtTableHeader>

      <ArtTable
        ref="tableRef"
        :loading="loading"
        :data="data"
        :columns="columns"
        :pagination="pagination"
        rowKey="id"
        @selection-change="handleSelectionChange"
        @pagination:size-change="handleSizeChange"
        @pagination:current-change="handleCurrentChange"
      />

      <UserDialog
        v-model:visible="dialogVisible"
        :type="dialogType"
        :user-data="currentUserData"
        :show-permission="canManagePermission"
        @success="handleDialogSuccess"
      />
    </ElCard>
  </div>
</template>

<script setup lang="ts">
  import ArtButtonTable from '@/components/core/forms/art-button-table/index.vue'
  import { useAuth } from '@/hooks'
  import { useBatchDelete } from '@/hooks/core/useBatchDelete'
  import { useTable } from '@/hooks/core/useTable'
  import type { TableFilterFormModel } from '@/types'
  import {
    fetchChangeUserStatus,
    fetchDeleteUser,
    fetchGetUserList,
    fetchResetUserPassword
  } from '@/api/system-manage'
  import { buildDynamicTableFilterParams, createTableFilterFormModel } from '@/utils/table/filter'
  import UserDialog from './modules/user-dialog.vue'
  import { createUserFilterFields } from './modules/user-filter-fields'
  import UserSearch from './modules/user-search.vue'
  import { ElAvatar, ElMessage, ElMessageBox, ElSwitch } from 'element-plus'

  defineOptions({ name: 'User' })

  type DialogType = 'add' | 'edit'
  type UserListItem = Api.SystemManage.UserListItem

  const { hasAuth } = useAuth()
  const canDeleteUser = hasAuth('system:user:delete')
  // 权限设置收进新增/编辑管理员弹窗，读取和保存权限都需要授权。
  const canManagePermission =
    hasAuth('system:authAccess:index') && hasAuth('system:authAccess:save')
  const PROTECTED_ADMIN_MESSAGE = '管理员账号不允许操作'

  const tableRef = ref()
  const dialogVisible = ref(false)
  const dialogType = ref<DialogType>('add')
  const currentUserData = ref<Partial<UserListItem>>({})
  const selectedRows = ref<UserListItem[]>([])
  const searchForm = ref<TableFilterFormModel>(createTableFilterFormModel())

  const filterFields = computed(() => createUserFilterFields())

  // 超级管理员默认拥有全部权限，不允许在管理员列表里编辑、删除或单独授权。
  const isAdminAccount = (row?: Partial<UserListItem>) =>
    Number(row?.is_admin || 0) === 1 || String(row?.username || '').toLowerCase() === 'admin'

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
        pageSize: 20
      },
      columnsFactory: () => [
        ...(canDeleteUser
          ? [
              {
                type: 'selection' as const,
                width: 55,
                fixed: 'left' as const,
                disabled: true,
                selectable: (row: UserListItem) => !isAdminAccount(row)
              }
            ]
          : []),
        {
          type: 'globalIndex',
          label: '序号',
          width: 70,
          fixed: 'left'
        },
        {
          prop: 'username',
          label: '管理员信息',
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
          prop: 'phone',
          label: '手机号',
          minWidth: 130,
          formatter: (row: UserListItem) => row.phone || '-'
        },
        {
          prop: 'email',
          label: '邮箱',
          minWidth: 180,
          formatter: (row: UserListItem) => row.email || '-',
          showOverflowTooltip: true
        },
        {
          prop: 'status',
          label: '状态',
          width: 120,
          formatter: (row: UserListItem) =>
            h(ElSwitch, {
              disabled: Number(row.is_admin || 0) === 1,
              modelValue: String(row.status) === '1',
              activeText: '启用',
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

            if (hasAuth('system:user:update') && !isAdminAccount(row)) {
              buttons.push(
                h(ArtButtonTable, {
                  type: 'edit',
                  onClick: () => openDialog('edit', row)
                })
              )
            }

            if (hasAuth('system:user:resetPassword') && !isAdminAccount(row)) {
              buttons.push(
                h(ArtButtonTable, {
                  icon: 'ri:lock-password-line',
                  iconClass: 'bg-warning/12 text-warning',
                  tooltip: '重置密码',
                  onClick: () => handleResetPassword(row)
                })
              )
            }

            if (canDeleteUser && !isAdminAccount(row)) {
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

  const { batchDeleting, hasSelection, handleSelectionChange, handleBatchDelete } =
    useBatchDelete<UserListItem>({
      selectedRows,
      getLabel: (row) => row.realname || row.username || `ID ${row.id}`,
      deleteFn: (row) =>
        fetchDeleteUser(row.id, {
          showSuccessMessage: false,
          showErrorMessage: false
        }),
      refreshFn: refreshRemove,
      clearSelection: () => tableRef.value?.elTableRef?.clearSelection?.()
    })

  const openDialog = (type: DialogType, row?: UserListItem) => {
    dialogType.value = type
    currentUserData.value = row || {}
    dialogVisible.value = true
  }

  const handleSearch = (params: TableFilterFormModel) => {
    replaceSearchParams(buildDynamicTableFilterParams(params, filterFields.value))
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
      `确定重置管理员“${row.realname || row.username}”的密码吗？`,
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
    if (isAdminAccount(row)) {
      ElMessage.warning(PROTECTED_ADMIN_MESSAGE)
      return
    }

    await ElMessageBox.confirm(`确定删除管理员“${row.realname || row.username}”吗？`, '删除确认', {
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
</script>
