<template>
  <div class="table-advanced-filter">
    <div class="summary-bar">
      <div class="summary-content">
        <span class="summary-label">高级过滤</span>
        <span class="summary-text" :title="summaryText">{{ summaryText }}</span>
      </div>

      <div class="summary-actions">
        <ElButton class="summary-button advanced-button" @click="openDialog">高级过滤</ElButton>
        <ElButton v-if="hasActiveFilters" class="summary-button clear-button" @click="clearFilters">
          清空
        </ElButton>
      </div>
    </div>

    <ElDialog
      v-model="dialogVisible"
      title="高级过滤"
      width="1180px"
      top="6vh"
      append-to-body
      destroy-on-close
      class="table-advanced-filter-dialog"
    >
      <div class="dialog-shell">
        <div class="dialog-hero">
          <div>
            <div class="hero-title">按分组组合复杂过滤条件</div>
            <div class="hero-description">组内按“且”匹配，组与组之间按“或”匹配。</div>
          </div>

          <div class="hero-metrics">
            <span class="metric-chip">或分组 {{ draftGroupCount }}</span>
            <span class="metric-chip">有效条件 {{ draftConditionCount }}</span>
          </div>
        </div>

        <div class="dialog-content">
          <TableFilterBuilder v-model="draftFilters" :fields="fields" />
        </div>
      </div>

      <template #footer>
        <div class="dialog-footer">
          <ElButton @click="resetDraft">清空条件</ElButton>
          <ElButton @click="handleCancel">取消</ElButton>
          <ElButton type="primary" @click="handleConfirm">应用过滤</ElButton>
        </div>
      </template>
    </ElDialog>
  </div>
</template>

<script setup lang="ts">
  import type { TableFilterFieldSchema, TableFilterGroup } from '@/types'
  import {
    buildTableFilterSummary,
    cloneTableFilterGroups,
    createEmptyTableFilterGroup,
    normalizeTableFilterGroups
  } from '@/utils/table/filter'
  import TableFilterBuilder from './table-filter-builder.vue'

  defineOptions({ name: 'TableAdvancedFilter' })

  interface Props {
    modelValue?: TableFilterGroup[]
    fields?: TableFilterFieldSchema[]
  }

  interface Emits {
    (e: 'update:modelValue', value: TableFilterGroup[]): void
    (e: 'apply', value: TableFilterGroup[]): void
  }

  const props = withDefaults(defineProps<Props>(), {
    modelValue: () => [],
    fields: () => []
  })

  const emit = defineEmits<Emits>()

  const dialogVisible = ref(false)
  const draftFilters = ref<TableFilterGroup[]>([])

  const normalizedGroups = computed(() =>
    normalizeTableFilterGroups(props.modelValue, props.fields)
  )
  const normalizedDraftGroups = computed(() =>
    normalizeTableFilterGroups(draftFilters.value, props.fields)
  )

  const activeConditionCount = computed(() =>
    normalizedGroups.value.reduce((count, group) => count + group.conditions.length, 0)
  )
  const hasActiveFilters = computed(() => activeConditionCount.value > 0)

  const draftGroupCount = computed(() => normalizedDraftGroups.value.length)
  const draftConditionCount = computed(() =>
    normalizedDraftGroups.value.reduce((count, group) => count + group.conditions.length, 0)
  )

  const summaryText = computed(() => buildTableFilterSummary(props.modelValue, props.fields))

  const emitFilters = (groups: TableFilterGroup[]) => {
    const nextFilters = cloneTableFilterGroups(groups)
    emit('update:modelValue', nextFilters)
    return nextFilters
  }

  const openDialog = () => {
    draftFilters.value = cloneTableFilterGroups(props.modelValue)
    dialogVisible.value = true
  }

  const clearFilters = () => {
    const nextFilters = emitFilters([createEmptyTableFilterGroup()])
    emit('apply', nextFilters)
  }

  const resetDraft = () => {
    draftFilters.value = [createEmptyTableFilterGroup()]
  }

  const handleCancel = () => {
    dialogVisible.value = false
  }

  const handleConfirm = () => {
    const nextFilters = emitFilters(draftFilters.value)
    emit('apply', nextFilters)
    dialogVisible.value = false
  }
</script>

<style lang="scss" scoped>
  .table-advanced-filter {
    width: 100%;
  }

  .summary-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    min-height: 32px;
    padding: 0 0 0 12px;
    background:
      linear-gradient(135deg, rgb(14 165 233 / 6%), rgb(59 130 246 / 2%)), var(--el-bg-color);
    border: 1px solid var(--el-border-color-lighter);
    border-radius: 8px;
  }

  .summary-content {
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 0;
    flex: 1;
  }

  .summary-label {
    flex-shrink: 0;
    color: var(--el-text-color-secondary);
    font-size: 12px;
    font-weight: 600;
  }

  .summary-text {
    min-width: 0;
    color: var(--el-text-color-primary);
    font-size: 13px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  .summary-actions {
    display: flex;
    flex-shrink: 0;
    gap: 8px;
  }

  .summary-button {
    height: 32px;
    padding: 0 12px;
    border-radius: 8px;
  }

  .advanced-button {
    color: var(--el-color-primary);
    background: var(--el-color-primary-light-9);
    border-color: var(--el-color-primary-light-7);
  }

  .clear-button {
    color: var(--el-color-danger);
    background: var(--el-color-danger-light-9);
    border-color: var(--el-color-danger-light-7);
  }

  .dialog-shell {
    display: flex;
    flex-direction: column;
    gap: 16px;
    margin: 0 auto;
    padding: 4px 0 0;
  }

  .dialog-hero {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 18px 20px;
    background:
      radial-gradient(circle at top left, rgb(56 189 248 / 10%), transparent 40%),
      linear-gradient(135deg, rgb(255 255 255 / 96%), rgb(248 250 252 / 94%));
    border: 1px solid var(--el-border-color-lighter);
    border-radius: 18px;
  }

  .hero-title {
    color: var(--el-text-color-primary);
    font-size: 18px;
    font-weight: 700;
  }

  .hero-description {
    margin-top: 6px;
    color: var(--el-text-color-secondary);
    font-size: 13px;
  }

  .hero-metrics {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
  }

  .metric-chip {
    display: inline-flex;
    align-items: center;
    height: 32px;
    padding: 0 12px;
    color: #0f766e;
    background: rgb(20 184 166 / 10%);
    border: 1px solid rgb(20 184 166 / 18%);
    border-radius: 999px;
    font-size: 12px;
    font-weight: 600;
  }

  .dialog-content {
    max-height: calc(100vh - 260px);
    padding: 4px 4px 0 0;
    overflow: auto;
  }

  .dialog-footer {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    width: 100%;
  }

  :global(.table-advanced-filter-dialog.el-dialog) {
    display: flex;
    flex-direction: column;
    width: min(1180px, calc(100vw - 32px)) !important;
    max-height: calc(100dvh - 32px);
    margin-bottom: 0;
    border-radius: 20px;
  }

  :global(.table-advanced-filter-dialog.el-dialog .el-dialog__body) {
    flex: 1;
    min-height: 0;
    padding-top: 14px;
    overflow: hidden;
  }

  :global(.table-advanced-filter-dialog.el-dialog .el-dialog__footer) {
    flex-shrink: 0;
  }

  @media (width <= 900px) {
    .summary-bar,
    .dialog-hero {
      align-items: flex-start;
      flex-direction: column;
    }

    .summary-bar {
      padding: 10px 0 10px 12px;
    }

    .summary-actions,
    .dialog-footer {
      width: 100%;
    }

    .summary-actions {
      justify-content: flex-start;
    }

    .dialog-footer {
      justify-content: stretch;
    }

    .dialog-footer :deep(.el-button) {
      flex: 1;
    }

    :global(.table-advanced-filter-dialog.el-dialog) {
      width: calc(100vw - 24px) !important;
      max-height: calc(100dvh - 24px);
    }

    :global(.table-advanced-filter-dialog.el-dialog .el-dialog__body) {
      padding-right: 16px !important;
      padding-left: 16px !important;
    }

    .dialog-content {
      max-height: calc(100dvh - 300px);
    }
  }
</style>
