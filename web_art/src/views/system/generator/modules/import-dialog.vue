<template>
  <ElDialog v-model="visible" title="导入数据表" width="960px" align-center>
    <ArtSearchBar
      v-model="searchForm"
      :items="searchItems"
      :showExpand="false"
      @reset="handleReset"
      @search="handleSearch"
    />

    <ArtTable
      :loading="loading"
      :data="data"
      :columns="columns"
      :pagination="pagination"
      rowKey="name"
      @selection-change="handleSelectionChange"
      @pagination:size-change="handleSizeChange"
      @pagination:current-change="handleCurrentChange"
    />

    <template #footer>
      <ElButton @click="visible = false">取消</ElButton>
      <ElButton type="primary" :loading="submitting" @click="handleImport">导入</ElButton>
    </template>
  </ElDialog>
</template>

<script setup lang="ts">
  import { useTable } from '@/hooks/core/useTable'
  import { fetchGetDatabaseTables, fetchImportGeneratorTables } from '@/api/system-manage'

  interface Emits {
    (e: 'update:modelValue', value: boolean): void
    (e: 'success'): void
  }

  const props = defineProps<{
    modelValue: boolean
  }>()

  const emit = defineEmits<Emits>()
  const submitting = ref(false)
  const selectedRows = ref<any[]>([])

  const visible = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value)
  })

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
    data,
    loading,
    pagination,
    getData,
    replaceSearchParams,
    resetSearchParams,
    handleSizeChange,
    handleCurrentChange
  } = useTable({
    core: {
      apiFn: fetchGetDatabaseTables,
      apiParams: {
        page: 1,
        pageSize: 15,
        ...searchForm.value
      },
      paginationKey: {
        current: 'page',
        size: 'pageSize'
      },
      columnsFactory: () => [
        { type: 'selection' },
        { prop: 'name', label: '表名称', minWidth: 180 },
        { prop: 'comment', label: '表描述', minWidth: 180 },
        { prop: 'engine', label: '引擎', width: 100 },
        { prop: 'rows', label: '数据量', width: 100 }
      ]
    }
  })

  const handleSelectionChange = (selection: any[]) => {
    selectedRows.value = selection
  }

  const handleSearch = (params: Record<string, any>) => {
    replaceSearchParams(params)
    getData()
  }

  const handleReset = async () => {
    await resetSearchParams()
  }

  const handleImport = async () => {
    if (!selectedRows.value.length) {
      ElMessage.warning('请先选择要导入的数据表')
      return
    }

    submitting.value = true
    try {
      await fetchImportGeneratorTables(selectedRows.value)
      visible.value = false
      selectedRows.value = []
      emit('success')
    } finally {
      submitting.value = false
    }
  }
</script>
