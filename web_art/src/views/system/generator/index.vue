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
          <ElButton type="primary" @click="importDialogVisible = true" v-ripple
            >导入数据表</ElButton
          >
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

    <ImportDialog v-model="importDialogVisible" @success="refreshData" />
    <PreviewDialog v-model="previewVisible" :preview-data="previewData" />
  </div>
</template>

<script setup lang="ts">
  import ArtButtonTable from '@/components/core/forms/art-button-table/index.vue'
  import { useBatchDelete } from '@/hooks/core/useBatchDelete'
  import { useTable } from '@/hooks/core/useTable'
  import {
    fetchDeleteGenerator,
    fetchGetGeneratorList,
    fetchMakeGeneratorCode,
    fetchPreviewGeneratorCode
  } from '@/api/system-manage'
  import ImportDialog from './modules/import-dialog.vue'
  import PreviewDialog from './modules/preview-dialog.vue'
  import { ElMessageBox } from 'element-plus'

  defineOptions({ name: 'Generator' })

  type GeneratorItem = Api.SystemManage.GeneratorListItem & {
    update_time?: string
  }

  const router = useRouter()
  const tableRef = ref()
  const importDialogVisible = ref(false)
  const previewVisible = ref(false)
  const previewData = ref<Record<string, any>>({})
  const selectedRows = ref<GeneratorItem[]>([])

  const searchForm = ref<Record<string, any>>({
    table_name: undefined,
    table_comment: undefined
  })

  const searchItems = computed(() => [
    {
      label: '表名称',
      key: 'table_name',
      type: 'input',
      props: { clearable: true, placeholder: '请输入表名称' }
    },
    {
      label: '表描述',
      key: 'table_comment',
      type: 'input',
      props: { clearable: true, placeholder: '请输入表描述' }
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
      apiFn: fetchGetGeneratorList,
      apiParams: {
        page: 1,
        pageSize: 20,
        ...searchForm.value
      },
      columnsFactory: () => [
        { type: 'selection' as const, width: 55, fixed: 'left' as const, disabled: true },
        { prop: 'table_name', label: '表名称', minWidth: 180 },
        { prop: 'table_comment', label: '表描述', minWidth: 180 },
        { prop: 'create_time', label: '创建时间', minWidth: 140 },
        {
          prop: 'update_time',
          label: '更新时间',
          minWidth: 140,
          formatter: (row: GeneratorItem) => row.update_time || '-'
        },
        {
          prop: 'operation',
          label: '操作',
          width: 180,
          fixed: 'right',
          formatter: (row: GeneratorItem) =>
            h('div', [
              h(ArtButtonTable, {
                type: 'view',
                tooltip: '预览代码',
                onClick: () => handlePreview(row)
              }),
              h(ArtButtonTable, {
                type: 'edit',
                tooltip: '编辑配置',
                onClick: () => router.push(`/system/generator/${row.id}`)
              }),
              h(ArtButtonTable, {
                icon: 'ri:code-box-line',
                iconClass: 'bg-success/12 text-success',
                tooltip: '生成代码',
                onClick: () => handleGenerate(row)
              }),
              h(ArtButtonTable, {
                type: 'delete',
                onClick: () => handleDelete(row)
              })
            ])
        }
      ]
    }
  })

  const { batchDeleting, hasSelection, handleSelectionChange, handleBatchDelete } =
    useBatchDelete<GeneratorItem>({
      selectedRows,
      getLabel: (row) => row.table_name || `ID ${row.id}`,
      deleteFn: (row) =>
        fetchDeleteGenerator(row.id, {
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

  const handlePreview = async (row: GeneratorItem) => {
    previewData.value = await fetchPreviewGeneratorCode(row.id)
    previewVisible.value = true
  }

  const handleGenerate = async (row: GeneratorItem) => {
    const result = await fetchMakeGeneratorCode(row.id)
    await ElMessageBox.alert(
      result?.file ? `代码已生成，文件标识：${result.file}` : '代码生成成功',
      '生成成功',
      {
        confirmButtonText: '知道了'
      }
    )
  }

  const handleDelete = async (row: GeneratorItem) => {
    await ElMessageBox.confirm(`确定删除生成配置“${row.table_name}”吗？`, '删除确认', {
      type: 'warning',
      confirmButtonText: '确定',
      cancelButtonText: '取消'
    })

    await fetchDeleteGenerator(row.id)
    await refreshData()
  }
</script>
