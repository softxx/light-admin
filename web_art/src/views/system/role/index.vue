<template>
  <div class="art-full-height">
    <RoleSearch v-model="searchForm" @search="handleSearch" @reset="handleReset" />

    <ElCard class="art-table-card" style="margin-top: 12px">
      <ArtTableHeader v-model:columns="columnChecks" :loading="loading" @refresh="refreshData">
        <template #left>
          <ElButton v-auth="'system:role:save'" @click="openEditDialog('add')" v-ripple>
            新增角色
          </ElButton>
          <ElButton
            v-if="canDeleteRole"
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
    </ElCard>

    <RoleEditDialog
      v-model="dialogVisible"
      :dialog-type="dialogType"
      :role-data="currentRoleData"
      :department-options="departmentOptions"
      @success="handleEditSuccess"
    />

    <RolePermissionDialog
      v-model="permissionDialogVisible"
      :role-data="currentRoleData"
      @success="refreshData"
    />
  </div>
</template>

<script setup lang="ts">
  import ArtButtonTable from '@/components/core/forms/art-button-table/index.vue'
  import { useAuth } from '@/hooks'
  import { useBatchDelete } from '@/hooks/core/useBatchDelete'
  import { useTable } from '@/hooks/core/useTable'
  import { fetchDeleteRole, fetchGetDepartmentList, fetchGetRoleList } from '@/api/system-manage'
  import RoleEditDialog from './modules/role-edit-dialog.vue'
  import RolePermissionDialog from './modules/role-permission-dialog.vue'
  import RoleSearch from './modules/role-search.vue'
  import { ElMessage, ElMessageBox } from 'element-plus'

  defineOptions({ name: 'Role' })

  type DialogType = 'add' | 'edit'
  type RoleListItem = Api.SystemManage.RoleListItem

  const SUPER_ADMIN_ROLE_ID = '1'
  const PROTECTED_ROLE_MESSAGE = '超级管理员角色默认拥有全部权限，不支持维护'

  const { hasAuth } = useAuth()
  const canDeleteRole = hasAuth('system:role:delete')

  const searchForm = ref<Api.SystemManage.RoleSearchParams>({
    key: undefined
  })

  const tableRef = ref()
  const dialogVisible = ref(false)
  const permissionDialogVisible = ref(false)
  const dialogType = ref<DialogType>('add')
  const currentRoleData = ref<RoleListItem>()
  const departmentOptions = ref<Api.SystemManage.DepartmentOption[]>([])
  const selectedRows = ref<RoleListItem[]>([])

  const dataRangeMap: Record<string, string> = {
    '1': '全部数据',
    '2': '自定义数据',
    '3': '本人数据',
    '4': '部门数据',
    '5': '部门及以下数据'
  }

  const isProtectedRole = (row?: Partial<RoleListItem>) =>
    String(row?.id ?? '') === SUPER_ADMIN_ROLE_ID

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
    refreshUpdate,
    refreshRemove
  } = useTable({
    core: {
      apiFn: fetchGetRoleList,
      apiParams: {
        page: 1,
        pageSize: 20,
        key: undefined
      },
      columnsFactory: () => [
        ...(canDeleteRole
          ? [
              {
                type: 'selection' as const,
                width: 55,
                fixed: 'left' as const,
                disabled: true,
                selectable: (row: RoleListItem) => !isProtectedRole(row)
              }
            ]
          : []),
        {
          type: 'index',
          label: '序号',
          width: 70
        },
        {
          prop: 'name',
          label: '角色名称',
          minWidth: 140
        },
        {
          prop: 'role_key',
          label: '权限标识',
          minWidth: 160
        },
        {
          prop: 'data_range',
          label: '数据范围',
          minWidth: 140,
          formatter: (row: RoleListItem) => dataRangeMap[String(row.data_range || '')] || '-'
        },
        {
          prop: 'note',
          label: '备注',
          minWidth: 180,
          showOverflowTooltip: true
        },
        {
          prop: 'create_time',
          label: '创建时间',
          minWidth: 140
        },
        {
          prop: 'operation',
          label: '操作',
          width: 180,
          fixed: 'right',
          formatter: (row: RoleListItem) => {
            if (isProtectedRole(row)) {
              return h(
                'span',
                { class: 'text-xs text-[var(--art-gray-500)] whitespace-nowrap' },
                '默认全权限'
              )
            }

            const buttons = []

            if (hasAuth('system:authAccess:save')) {
              buttons.push(
                h(ArtButtonTable, {
                  icon: 'ri:shield-keyhole-line',
                  iconClass: 'bg-info/12 text-info',
                  tooltip: '权限设置',
                  onClick: () => openPermissionDialog(row)
                })
              )
            }

            if (hasAuth('system:role:update')) {
              buttons.push(
                h(ArtButtonTable, {
                  type: 'edit',
                  onClick: () => openEditDialog('edit', row)
                })
              )
            }

            if (hasAuth('system:role:delete')) {
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
    useBatchDelete<RoleListItem>({
      selectedRows,
      getLabel: (row) => row.name || `ID ${row.id}`,
      deleteFn: (row) =>
        fetchDeleteRole(row.id, {
          showSuccessMessage: false,
          showErrorMessage: false
        }),
      refreshFn: refreshRemove,
      clearSelection: () => tableRef.value?.elTableRef?.clearSelection?.()
    })

  const openEditDialog = (type: DialogType, row?: RoleListItem) => {
    if (type === 'edit' && isProtectedRole(row)) {
      ElMessage.warning(PROTECTED_ROLE_MESSAGE)
      return
    }

    dialogType.value = type
    currentRoleData.value = row
    dialogVisible.value = true
  }

  const openPermissionDialog = (row: RoleListItem) => {
    if (isProtectedRole(row)) {
      ElMessage.warning(PROTECTED_ROLE_MESSAGE)
      return
    }

    currentRoleData.value = row
    permissionDialogVisible.value = true
  }

  const handleDelete = async (row: RoleListItem) => {
    if (isProtectedRole(row)) {
      ElMessage.warning(PROTECTED_ROLE_MESSAGE)
      return
    }

    await ElMessageBox.confirm(`确定删除角色“${row.name}”吗？删除后不可恢复。`, '删除确认', {
      type: 'warning',
      confirmButtonText: '确定',
      cancelButtonText: '取消'
    })

    await fetchDeleteRole(row.id)
    await refreshRemove()
  }

  const handleSearch = (params: Api.SystemManage.RoleSearchParams) => {
    replaceSearchParams(params)
    getData()
  }

  const handleReset = async () => {
    await resetSearchParams()
  }

  const handleEditSuccess = async (type: DialogType) => {
    if (type === 'add') {
      await refreshCreate()
      return
    }

    await refreshUpdate()
  }

  onMounted(async () => {
    departmentOptions.value = await fetchGetDepartmentList()
  })
</script>
