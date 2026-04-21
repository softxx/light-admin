<template>
  <div class="art-full-height">
    <ArtSearchBar
      v-model="searchForm"
      :items="searchItems"
      :button-left-limit="0"
      :show-expand="false"
      @reset="handleReset"
      @search="handleSearch"
    />

    <ElCard class="art-table-card" style="margin-top: 12px">
      <ArtTableHeader v-model:columns="columnChecks" :loading="loading" @refresh="refreshData">
        <template #left>
          <ElButton @click="handleClear" v-ripple>清空日志</ElButton>
          <ElButton type="primary" @click="handleExport" v-ripple>导出日志</ElButton>
          <ElButton
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
  </div>
</template>

<script setup lang="ts">
  import { markRaw } from 'vue'
  import ArtButtonTable from '@/components/core/forms/art-button-table/index.vue'
  import { TableAdvancedFilter, TableQuickFilter } from '@/components/business/table-filters'
  import { useBatchDelete } from '@/hooks/core/useBatchDelete'
  import { useTable } from '@/hooks/core/useTable'
  import type { TableFilterFieldSchema, TableFilterFormModel, TableFilterGroup } from '@/types'
  import {
    fetchClearLoginLog,
    fetchDeleteLoginLog,
    fetchExportLoginLog,
    fetchGetLoginLogList
  } from '@/api/system-manage'
  import { buildDynamicTableFilterParams, createTableFilterFormModel } from '@/utils/table/filter'
  import { ElMessageBox } from 'element-plus'

  defineOptions({ name: 'LoginLog' })

  type LoginLogItem = Api.SystemManage.LogListItem & {
    account?: string
    realname?: string
    login_ip?: string
    os?: string
    browser?: string
    login_time?: string
  }

  const tableRef = ref()
  const quickFilterRenderer = markRaw(TableQuickFilter)
  const advancedFilterRenderer = markRaw(TableAdvancedFilter)
  const searchForm = ref<TableFilterFormModel>(createTableFilterFormModel())
  const selectedRows = ref<LoginLogItem[]>([])

  /**
   * Login log fields are defined once here and then reused by both the
   * reusable search UI and the page-level serializer.
   */
  const filterFields = computed<TableFilterFieldSchema[]>(() => [
    {
      label: '登录账号',
      value: 'account',
      type: 'text',
      placeholder: '请输入登录账号'
    },
    {
      label: '用户姓名',
      value: 'realname',
      type: 'text',
      placeholder: '请输入用户姓名'
    },
    {
      label: '登录 IP',
      value: 'login_ip',
      type: 'text',
      placeholder: '请输入登录 IP'
    },
    {
      label: '浏览器',
      value: 'browser',
      type: 'text',
      placeholder: '请输入浏览器'
    },
    {
      label: '操作系统',
      value: 'os',
      type: 'text',
      placeholder: '请输入操作系统'
    },
    {
      label: '登录时间',
      value: 'login_time',
      type: 'date',
      dateType: 'datetime',
      placeholder: '请选择登录时间'
    }
  ])

  const searchItems = computed(() => [
    {
      label: '快捷过滤',
      labelWidth: '84px',
      key: 'quickFilter',
      span: 12,
      render: quickFilterRenderer,
      props: {
        fields: filterFields.value
      }
    },
    {
      label: '高级过滤',
      labelWidth: '84px',
      key: 'advancedFilters',
      span: 6,
      render: advancedFilterRenderer,
      props: {
        fields: filterFields.value,
        onApply: handleAdvancedFilterApply
      }
    }
  ])

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
    refreshRemove
  } = useTable({
    core: {
      apiFn: fetchGetLoginLogList,
      apiParams: {
        page: 1,
        pageSize: 20
      },
      columnsFactory: () => [
        { type: 'selection' as const, width: 55, fixed: 'left' as const, disabled: true },
        { type: 'index', label: '序号', width: 70 },
        { prop: 'account', label: '登录账号', minWidth: 140 },
        {
          prop: 'realname',
          label: '用户姓名',
          minWidth: 120,
          formatter: (row: LoginLogItem) => row.realname || row.user?.realname || '-'
        },
        { prop: 'login_ip', label: '登录 IP', minWidth: 140 },
        { prop: 'os', label: '操作系统', minWidth: 120 },
        { prop: 'browser', label: '浏览器', minWidth: 120 },
        { prop: 'login_time', label: '登录时间', minWidth: 160 },
        {
          prop: 'operation',
          label: '操作',
          width: 90,
          fixed: 'right',
          formatter: (row: LoginLogItem) =>
            h(ArtButtonTable, {
              type: 'delete',
              onClick: () => handleDelete(row)
            })
        }
      ]
    }
  })

  const { batchDeleting, hasSelection, handleSelectionChange, handleBatchDelete } =
    useBatchDelete<LoginLogItem>({
      selectedRows,
      getLabel: (row) => row.account || row.realname || `ID ${row.id}`,
      deleteFn: (row) =>
        fetchDeleteLoginLog(row.id, {
          showSuccessMessage: false,
          showErrorMessage: false
        }),
      refreshFn: refreshRemove,
      clearSelection: () => tableRef.value?.elTableRef?.clearSelection?.()
    })

  const performSearch = (params: Partial<TableFilterFormModel>) => {
    replaceSearchParams(buildDynamicTableFilterParams(params, filterFields.value))
    getData()
  }

  const handleSearch = (params: Record<string, any>) => {
    performSearch(params as TableFilterFormModel)
  }

  const handleAdvancedFilterApply = (advancedFilters: TableFilterGroup[]) => {
    performSearch({
      quickFilter: searchForm.value.quickFilter,
      advancedFilters
    })
  }

  const handleReset = async () => {
    await resetSearchParams()
  }

  const handleDelete = async (row: LoginLogItem) => {
    await ElMessageBox.confirm('确定删除这条登录日志吗？', '删除确认', {
      type: 'warning',
      confirmButtonText: '确定',
      cancelButtonText: '取消'
    })

    await fetchDeleteLoginLog(row.id)
    await refreshData()
  }

  const handleClear = async () => {
    await ElMessageBox.confirm('确定清空全部登录日志吗？该操作不可恢复。', '清空确认', {
      type: 'warning',
      confirmButtonText: '确定',
      cancelButtonText: '取消'
    })

    await fetchClearLoginLog()
    await refreshData()
  }

  const handleExport = async () => {
    const blob = await fetchExportLoginLog(
      buildDynamicTableFilterParams(searchForm.value, filterFields.value)
    )
    const url = URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = `login-log-${Date.now()}.xlsx`
    link.click()
    URL.revokeObjectURL(url)
  }
</script>
