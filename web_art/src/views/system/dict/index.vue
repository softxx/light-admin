<template>
  <div class="dict-page art-full-height">
    <div class="grid gap-3 h-full md:grid-cols-[300px_minmax(0,1fr)]">
      <ElCard class="h-full overflow-hidden">
        <ElInput v-model="typeKeyword" clearable placeholder="请输入字典名称搜索" />

        <div class="mt-4 h-[calc(100%-92px)] overflow-auto">
          <div
            v-for="item in filteredTypeList"
            :key="item.id"
            class="dict-type-item"
            :class="{ active: currentType?.id === item.id }"
            @click="selectType(item)"
          >
            <div class="min-w-0 flex-1 pr-3">
              <div class="font-medium truncate">{{ item.name }}</div>
              <div class="text-xs text-[var(--art-gray-500)] mt-1 truncate">{{ item.value }}</div>
            </div>

            <div class="dict-type-item__meta" @click.stop>
              <ElTag :type="String(item.status) === '1' ? 'success' : 'warning'">
                {{ String(item.status) === '1' ? '启用' : '禁用' }}
              </ElTag>

              <ArtButtonMore
                v-if="getTypeActionList(item).length"
                :list="getTypeActionList(item)"
                @click="(action) => handleTypeAction(action.key as DictTypeActionKey, item)"
              />
            </div>
          </div>
        </div>

        <div class="mt-4 flex gap-2">
          <ElButton @click="handleUpdateCache">更新缓存</ElButton>
          <ElButton v-if="canSaveDict" type="primary" @click="openDialog('dict_type')"
            >新增类型</ElButton
          >
        </div>
      </ElCard>

      <ElCard class="art-table-card h-full">
        <div class="flex-cb mb-4">
          <div>
            <div class="text-lg font-semibold">{{ currentType?.name || '字典数据' }}</div>
            <div class="text-sm text-[var(--art-gray-500)]">{{ currentType?.value || '-' }}</div>
          </div>

          <div class="flex gap-2">
            <ElButton
              v-if="canSaveDict"
              type="primary"
              :disabled="!currentType"
              @click="openDialog(String(currentType?.value || ''), null)"
            >
              新增条目
            </ElButton>
            <ElButton
              v-if="canDeleteDict"
              type="danger"
              :disabled="!hasSelection"
              :loading="batchDeleting"
              @click="handleBatchDelete"
              v-ripple
            >
              批量删除
            </ElButton>
          </div>
        </div>

        <ArtTableHeader
          :loading="loading"
          v-model:columns="columnChecks"
          @refresh="loadCurrentItems"
        />

        <ArtTable
          ref="tableRef"
          :loading="loading"
          :data="tableData"
          :columns="columns"
          rowKey="id"
          :stripe="false"
          @selection-change="handleSelectionChange"
        />
      </ElCard>
    </div>

    <DictDialog
      v-model="dialogVisible"
      :dict-type="dialogType"
      :edit-data="currentDictData"
      @success="handleDialogSuccess"
    />
  </div>
</template>

<script setup lang="ts">
  import ArtButtonMore from '@/components/core/forms/art-button-more/index.vue'
  import ArtButtonTable from '@/components/core/forms/art-button-table/index.vue'
  import { useAuth } from '@/hooks'
  import { useBatchDelete } from '@/hooks/core/useBatchDelete'
  import { useTableColumns } from '@/hooks/core/useTableColumns'
  import {
    fetchChangeDictStatus,
    fetchDeleteDict,
    fetchGetDictList,
    fetchUpdateDictCache
  } from '@/api/system-manage'
  import DictDialog from './modules/dict-dialog.vue'
  import { ElMessageBox, ElTag } from 'element-plus'

  defineOptions({ name: 'Dict' })

  type DictItem = Api.SystemManage.DictListItem
  type DictTypeActionKey = 'edit' | 'status' | 'delete'

  const { hasAuth } = useAuth()
  const canSaveDict = hasAuth('system:dict:save')
  const canUpdateDict = hasAuth('system:dict:update')
  const canDeleteDict = hasAuth('system:dict:delete')

  const loading = ref(false)
  const tableRef = ref()
  const typeKeyword = ref('')
  const typeList = ref<DictItem[]>([])
  const currentType = ref<DictItem>()
  const tableData = ref<DictItem[]>([])
  const dialogVisible = ref(false)
  const dialogType = ref('dict_type')
  const currentDictData = ref<Partial<DictItem> | null>(null)
  const selectedRows = ref<DictItem[]>([])

  const createTableActions = (row: DictItem) => {
    const actions = []

    if (canUpdateDict) {
      actions.push(
        h(ArtButtonTable, {
          type: 'edit',
          onClick: () => openDialog(row.type, row)
        }),
        h(ArtButtonTable, {
          icon: String(row.status) === '1' ? 'ri:stop-circle-line' : 'ri:checkbox-circle-line',
          iconClass:
            String(row.status) === '1'
              ? 'bg-warning/12 text-warning'
              : 'bg-success/12 text-success',
          tooltip: String(row.status) === '1' ? '禁用' : '启用',
          onClick: () => handleChangeStatus(row)
        })
      )
    }

    if (canDeleteDict) {
      actions.push(
        h(ArtButtonTable, {
          type: 'delete',
          onClick: () => handleDelete(row)
        })
      )
    }

    return actions
  }

  const { columnChecks, columns } = useTableColumns(() => [
    ...(canDeleteDict
      ? [{ type: 'selection' as const, width: 55, fixed: 'left' as const, disabled: true }]
      : []),
    {
      type: 'index',
      label: '序号',
      width: 70
    },
    {
      prop: 'name',
      label: '字典名称',
      minWidth: 140
    },
    {
      prop: 'value',
      label: '字典值',
      minWidth: 140
    },
    {
      prop: 'widget_type',
      label: '组件类型',
      width: 100,
      formatter: (row: DictItem) => row.widget_type || '-'
    },
    {
      prop: 'color',
      label: '颜色',
      width: 100,
      formatter: (row: DictItem) => row.color || '-'
    },
    {
      prop: 'status',
      label: '状态',
      width: 90,
      formatter: (row: DictItem) =>
        h(ElTag, { type: String(row.status) === '1' ? 'success' : 'warning' }, () =>
          String(row.status) === '1' ? '启用' : '禁用'
        )
    },
    {
      prop: 'note',
      label: '备注',
      minWidth: 180,
      showOverflowTooltip: true
    },
    ...(canUpdateDict || canDeleteDict
      ? [
          {
            prop: 'operation',
            label: '操作',
            width: 150,
            fixed: 'right' as const,
            formatter: (row: DictItem) => h('div', createTableActions(row))
          }
        ]
      : [])
  ])

  const clearTableSelection = () => {
    selectedRows.value = []
    nextTick(() => {
      tableRef.value?.elTableRef?.clearSelection?.()
    })
  }

  const refreshAfterMutation = async (targetType?: string) => {
    if (targetType === 'dict_type') {
      await loadTypes()
      return
    }

    await loadCurrentItems()
    await loadTypes()
  }

  const { batchDeleting, hasSelection, handleSelectionChange, handleBatchDelete } =
    useBatchDelete<DictItem>({
      selectedRows,
      getLabel: (row) => row.name || `ID ${row.id}`,
      deleteFn: (row) =>
        fetchDeleteDict(row.id, {
          showSuccessMessage: false,
          showErrorMessage: false
        }),
      refreshFn: async () => {
        await refreshAfterMutation(
          currentType.value?.value ? String(currentType.value.value) : undefined
        )
      },
      clearSelection: clearTableSelection
    })

  const filteredTypeList = computed(() => {
    const keyword = typeKeyword.value.trim().toLowerCase()
    if (!keyword) {
      return typeList.value
    }

    return typeList.value.filter((item) => item.name?.toLowerCase().includes(keyword))
  })

  const getTypeActionList = (item: DictItem) => {
    const actions: Array<{
      key: DictTypeActionKey
      label: string
      icon: string
      color?: string
    }> = []

    if (canUpdateDict) {
      actions.push(
        {
          key: 'edit',
          label: '编辑类型',
          icon: 'ri:pencil-line'
        },
        {
          key: 'status',
          label: String(item.status) === '1' ? '禁用类型' : '启用类型',
          icon: String(item.status) === '1' ? 'ri:stop-circle-line' : 'ri:checkbox-circle-line'
        }
      )
    }

    if (canDeleteDict) {
      actions.push({
        key: 'delete',
        label: '删除类型',
        icon: 'ri:delete-bin-5-line',
        color: 'var(--el-color-danger)'
      })
    }

    return actions
  }

  const loadTypes = async (keepCurrent = true) => {
    typeList.value = await fetchGetDictList({ type: 'dict_type' })
    clearTableSelection()

    if (!typeList.value.length) {
      currentType.value = undefined
      tableData.value = []
      return
    }

    if (keepCurrent && currentType.value) {
      currentType.value =
        typeList.value.find((item) => item.id === currentType.value?.id) || typeList.value[0]
    } else {
      currentType.value = typeList.value[0]
    }

    await loadCurrentItems()
  }

  const loadCurrentItems = async () => {
    if (!currentType.value) {
      tableData.value = []
      clearTableSelection()
      return
    }

    loading.value = true
    try {
      tableData.value = await fetchGetDictList({ type: currentType.value.value })
      clearTableSelection()
    } finally {
      loading.value = false
    }
  }

  const selectType = (item: DictItem) => {
    currentType.value = item
    loadCurrentItems()
  }

  const openDialog = (dictType: string, row?: DictItem | null) => {
    dialogType.value = dictType
    currentDictData.value = row || null
    dialogVisible.value = true
  }

  const handleTypeAction = async (action: DictTypeActionKey, item: DictItem) => {
    if (action === 'edit') {
      openDialog('dict_type', item)
      return
    }

    if (action === 'status') {
      await handleChangeStatus(item)
      return
    }

    await handleDelete(item)
  }

  const handleDelete = async (row: DictItem) => {
    const isTypeRow = row.type === 'dict_type'
    const message = isTypeRow
      ? `确定删除字典类型“${row.name}”吗？删除后会同时删除该类型下的所有字典项。`
      : `确定删除字典项“${row.name}”吗？`

    await ElMessageBox.confirm(message, '删除确认', {
      type: 'warning',
      confirmButtonText: '确定',
      cancelButtonText: '取消'
    })

    await fetchDeleteDict(row.id)
    await refreshAfterMutation(row.type)
  }

  const handleChangeStatus = async (row: DictItem) => {
    await fetchChangeDictStatus(row.id)
    await refreshAfterMutation(row.type)
  }

  const handleUpdateCache = async () => {
    await fetchUpdateDictCache()
    await loadTypes()
  }

  const handleDialogSuccess = async () => {
    await refreshAfterMutation(dialogType.value)
  }

  onMounted(() => {
    loadTypes(false)
  })
</script>

<style scoped>
  .dict-type-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 12px 14px;
    border-radius: 10px;
    cursor: pointer;
    transition: 0.2s ease;
  }

  .dict-type-item:hover {
    background: var(--art-main-bg-color);
  }

  .dict-type-item.active {
    background: color-mix(in srgb, var(--el-color-primary) 10%, transparent);
  }

  .dict-type-item__meta {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-shrink: 0;
  }
</style>
