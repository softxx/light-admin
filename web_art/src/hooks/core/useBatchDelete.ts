import { computed, ref } from 'vue'
import type { Ref } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'

interface UseBatchDeleteOptions<T> {
  selectedRows: Ref<T[]>
  deleteFn: (row: T) => Promise<unknown>
  refreshFn: () => Promise<unknown>
  getLabel?: (row: T) => string
  sortRows?: (rows: T[]) => T[]
  clearSelection?: () => void
}

const getErrorMessage = (error: unknown, fallback: string) => {
  if (
    error &&
    typeof error === 'object' &&
    'message' in error &&
    typeof error.message === 'string' &&
    error.message
  ) {
    return error.message
  }

  return fallback
}

export function useBatchDelete<T>(options: UseBatchDeleteOptions<T>) {
  const batchDeleting = ref(false)
  const selectedCount = computed(() => options.selectedRows.value.length)
  const hasSelection = computed(() => selectedCount.value > 0)

  const resetSelection = () => {
    options.selectedRows.value = []
    options.clearSelection?.()
  }

  const handleSelectionChange = (rows: T[]) => {
    options.selectedRows.value = rows
  }

  const handleBatchDelete = async () => {
    if (!hasSelection.value) {
      ElMessage.warning('请先选择要删除的数据')
      return
    }

    await ElMessageBox.confirm(
      `确定批量删除已选中的 ${selectedCount.value} 项数据吗？`,
      '批量删除确认',
      {
        type: 'warning',
        confirmButtonText: '确定',
        cancelButtonText: '取消'
      }
    )

    const rows = options.sortRows
      ? options.sortRows([...options.selectedRows.value])
      : [...options.selectedRows.value]

    let successCount = 0
    const failedMessages: string[] = []

    batchDeleting.value = true

    try {
      for (const row of rows) {
        try {
          await options.deleteFn(row)
          successCount += 1
        } catch (error) {
          const label = options.getLabel?.(row)
          const message = getErrorMessage(error, '删除失败')
          failedMessages.push(label ? `${label}：${message}` : message)
        }
      }

      await options.refreshFn()
      resetSelection()

      if (!failedMessages.length) {
        ElMessage.success(`已删除 ${successCount} 项数据`)
        return
      }

      if (!successCount) {
        ElMessage.error(failedMessages[0] || '批量删除失败')
        return
      }

      ElMessage.warning(
        `已删除 ${successCount} 项，${failedMessages.length} 项删除失败${
          failedMessages[0] ? `，例如：${failedMessages[0]}` : ''
        }`
      )
    } finally {
      batchDeleting.value = false
    }
  }

  return {
    batchDeleting,
    selectedCount,
    hasSelection,
    resetSelection,
    handleSelectionChange,
    handleBatchDelete
  }
}
