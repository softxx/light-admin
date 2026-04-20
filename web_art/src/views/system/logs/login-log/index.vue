<template>
  <div class="art-full-height">
    <ArtSearchBar
      v-model="searchForm"
      :items="searchItems"
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
  import ArtButtonTable from '@/components/core/forms/art-button-table/index.vue'
  import { useBatchDelete } from '@/hooks/core/useBatchDelete'
  import { useTable } from '@/hooks/core/useTable'
  import {
    fetchClearLoginLog,
    fetchDeleteLoginLog,
    fetchExportLoginLog,
    fetchGetLoginLogList
  } from '@/api/system-manage'
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
  const searchForm = ref<Record<string, any>>({
    account: undefined,
    login_ip: undefined,
    login_time: undefined
  })
  const selectedRows = ref<LoginLogItem[]>([])

  const searchItems = computed(() => [
    {
      label: '登录账号',
      key: 'account',
      type: 'input',
      props: { clearable: true, placeholder: '请输入登录账号' }
    },
    {
      label: '登录 IP',
      key: 'login_ip',
      type: 'input',
      props: { clearable: true, placeholder: '请输入登录 IP' }
    },
    {
      label: '登录时间',
      key: 'login_time',
      type: 'daterange',
      props: {
        type: 'daterange',
        clearable: true,
        style: { width: '100%' },
        startPlaceholder: '开始日期',
        endPlaceholder: '结束日期',
        rangeSeparator: '至',
        valueFormat: 'YYYY-MM-DD'
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
        pageSize: 20,
        ...searchForm.value
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

  const handleSearch = (params: Record<string, any>) => {
    replaceSearchParams(params)
    getData()
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
    await ElMessageBox.confirm('确定清空全部登录日志吗？', '清空确认', {
      type: 'warning',
      confirmButtonText: '确定',
      cancelButtonText: '取消'
    })

    await fetchClearLoginLog()
    await refreshData()
  }

  const handleExport = async () => {
    const blob = await fetchExportLoginLog()
    const url = URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = `login-log-${Date.now()}.xlsx`
    link.click()
    URL.revokeObjectURL(url)
  }
</script>
