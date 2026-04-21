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
  import { useBatchDelete } from '@/hooks/core/useBatchDelete'
  import { useTable } from '@/hooks/core/useTable'
  import type { TableFilterFieldSchema, TableFilterFormModel, TableFilterGroup } from '@/types'
  import {
    fetchClearOperateLog,
    fetchDeleteOperateLog,
    fetchGetActiveUsers,
    fetchGetOperateLogList
  } from '@/api/system-manage'
  import { buildDynamicTableFilterParams, createTableFilterFormModel } from '@/utils/table/filter'
  import { ElMessageBox, ElPopover } from 'element-plus'
  import OperateLogAdvancedFilter from './modules/operate-log-advanced-filter.vue'
  import OperateLogQuickFilter from './modules/operate-log-quick-filter.vue'

  defineOptions({ name: 'OperateLog' })

  type OperateLogItem = Api.SystemManage.LogListItem & {
    user_id?: number | string
    realname?: string
    module?: string
    operate?: string
    route?: string
    method?: string
    ip?: string
    params?: string
    create_time?: string
  }

  const tableRef = ref()
  const userOptions = ref<Array<{ label: string; value: number | string }>>([])
  const advancedFilterRenderer = markRaw(OperateLogAdvancedFilter)
  const quickFilterRenderer = markRaw(OperateLogQuickFilter)

  const searchForm = ref<TableFilterFormModel>(createTableFilterFormModel())
  const selectedRows = ref<OperateLogItem[]>([])

  const methodOptions = [
    { label: 'GET', value: 'GET' },
    { label: 'POST', value: 'POST' },
    { label: 'PUT', value: 'PUT' },
    { label: 'DELETE', value: 'DELETE' }
  ]

  const filterFields = computed<TableFilterFieldSchema[]>(() => [
    {
      label: '操作人',
      value: 'user_id',
      type: 'special-select',
      options: userOptions.value,
      placeholder: '请选择操作人',
      containsPlaceholder: '请输入操作人姓名'
    },
    {
      label: '请求方式',
      value: 'method',
      type: 'select',
      options: methodOptions,
      placeholder: '请选择请求方式'
    },
    {
      label: '操作模块',
      value: 'module',
      type: 'text',
      placeholder: '请输入操作模块'
    },
    {
      label: '操作行为',
      value: 'operate',
      type: 'text',
      placeholder: '请输入操作行为'
    },
    {
      label: '权限节点',
      value: 'route',
      type: 'text',
      placeholder: '请输入权限节点'
    },
    {
      label: 'IP 地址',
      value: 'ip',
      type: 'text',
      placeholder: '请输入 IP 地址'
    },
    {
      label: '请求参数',
      value: 'params',
      type: 'text',
      placeholder: '请输入请求参数'
    },
    {
      label: '操作时间',
      value: 'create_time',
      type: 'date',
      placeholder: '请选择操作时间'
    }
  ])

  const searchItems = computed(() => [
    {
      label: '快速过滤',
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
      apiFn: fetchGetOperateLogList,
      apiParams: {
        page: 1,
        pageSize: 20
      },
      columnsFactory: () => [
        { type: 'selection' as const, width: 55, fixed: 'left' as const, disabled: true },
        { type: 'index', label: '序号', width: 70 },
        { prop: 'id', label: '日志 ID', width: 90 },
        {
          prop: 'realname',
          label: '操作人',
          minWidth: 120,
          formatter: (row: OperateLogItem) => row.realname || row.user?.realname || '-'
        },
        { prop: 'module', label: '操作模块', minWidth: 120 },
        { prop: 'operate', label: '操作行为', minWidth: 140 },
        { prop: 'route', label: '权限节点', minWidth: 180 },
        { prop: 'method', label: '请求方式', width: 100 },
        { prop: 'ip', label: 'IP 地址', minWidth: 140 },
        {
          prop: 'params',
          label: '请求参数',
          minWidth: 120,
          formatter: (row: OperateLogItem) =>
            h(
              ElPopover,
              {
                width: 420,
                trigger: 'click'
              },
              {
                default: () =>
                  h(
                    'pre',
                    { class: 'text-xs whitespace-pre-wrap break-all m-0' },
                    row.params || '-'
                  ),
                reference: () =>
                  h('span', { class: 'text-[var(--el-color-primary)] cursor-pointer' }, '查看')
              }
            )
        },
        { prop: 'create_time', label: '操作时间', minWidth: 180 },
        {
          prop: 'operation',
          label: '操作',
          width: 90,
          fixed: 'right',
          formatter: (row: OperateLogItem) =>
            h(ArtButtonTable, {
              type: 'delete',
              onClick: () => handleDelete(row)
            })
        }
      ]
    }
  })

  const { batchDeleting, hasSelection, handleSelectionChange, handleBatchDelete } =
    useBatchDelete<OperateLogItem>({
      selectedRows,
      getLabel: (row) => row.realname || `ID ${row.id}`,
      deleteFn: (row) =>
        fetchDeleteOperateLog(row.id, {
          showSuccessMessage: false,
          showErrorMessage: false
        }),
      refreshFn: refreshRemove,
      clearSelection: () => tableRef.value?.elTableRef?.clearSelection?.()
    })

  // Keep page logic focused on field schema and data fetching. The shared
  // serializer owns the quick_filter / filters transport details.
  const performSearch = (params: Partial<TableFilterFormModel>) => {
    replaceSearchParams(buildDynamicTableFilterParams(params, filterFields.value))
    getData()
  }

  const handleAdvancedFilterApply = (advancedFilters: TableFilterGroup[]) => {
    performSearch({
      quickFilter: searchForm.value.quickFilter,
      advancedFilters
    })
  }

  const loadUsers = async () => {
    const users = await fetchGetActiveUsers({
      page: 1,
      pageSize: 500
    })
    const list = users.list || users.data || users.records || []
    userOptions.value = list.map((item: any) => ({
      label: item.realname || item.username || `用户 ${item.id}`,
      value: item.id
    }))
  }

  const handleSearch = (params: Record<string, any>) => {
    performSearch(params as TableFilterFormModel)
  }

  const handleReset = async () => {
    await resetSearchParams()
  }

  const handleDelete = async (row: OperateLogItem) => {
    await ElMessageBox.confirm('确定删除这条操作日志吗？', '删除确认', {
      type: 'warning',
      confirmButtonText: '确定',
      cancelButtonText: '取消'
    })

    await fetchDeleteOperateLog(row.id)
    await refreshData()
  }

  const handleClear = async () => {
    await ElMessageBox.confirm('确定清空全部操作日志吗？', '清空确认', {
      type: 'warning',
      confirmButtonText: '确定',
      cancelButtonText: '取消'
    })

    await fetchClearOperateLog()
    await refreshData()
  }

  onMounted(() => {
    loadUsers()
  })
</script>
