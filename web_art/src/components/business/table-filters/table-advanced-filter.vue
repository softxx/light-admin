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

  const normalizedGroups = computed(() => normalizeTableFilterGroups(props.modelValue, props.fields))
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
    gap: 10px;
    align-items: center;
    justify-content: space-between;
    min-height: 32px;
    padding: 0 0 0 12px;
    background:
      linear-gradient(135deg, rgb(14 165 233 / 6%), rgb(59 130 246 / 2%)), var(--el-bg-color);
    border: 1px solid var(--el-border-color-lighter);
    border-radius: 8px;
  }

  .summary-content {
    display: flex;
    flex: 1;
    gap: 10px;
    align-items: center;
    min-width: 0;
  }

  .summary-label {
    flex-shrink: 0;
    font-size: 12px;
    font-weight: 600;
    color: var(--el-text-color-secondary);
  }

  .summary-text {
    min-width: 0;
    overflow: hidden;
    font-size: 13px;
    color: var(--el-text-color-primary);
    text-overflow: ellipsis;
    white-space: nowrap;
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
    padding: 4px 0 0;
    margin: 0 auto;
  }

  .dialog-hero {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    align-items: center;
    justify-content: space-between;
    padding: 18px 20px;
    background:
      radial-gradient(
        circle at top left,
        color-mix(in srgb, var(--el-color-primary) 12%, transparent),
        transparent 40%
      ),
      linear-gradient(
        135deg,
        color-mix(in srgb, var(--el-bg-color) 96%, var(--el-color-primary) 4%),
        color-mix(in srgb, var(--el-bg-color) 94%, var(--el-fill-color-light))
      );
    border: 1px solid var(--el-border-color-lighter);
    border-radius: 18px;
  }

  .hero-title {
    font-size: 18px;
    font-weight: 700;
    color: var(--el-text-color-primary);
  }

  .hero-description {
    margin-top: 6px;
    font-size: 13px;
    color: var(--el-text-color-secondary);
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
    font-size: 12px;
    font-weight: 600;
    color: var(--el-color-success);
    background: color-mix(in srgb, var(--el-color-success) 12%, transparent);
    border: 1px solid color-mix(in srgb, var(--el-color-success) 24%, transparent);
    border-radius: 999px;
  }

  .dialog-content {
    max-height: calc(100vh - 260px);
    padding: 4px 4px 0 0;
    overflow: auto;
  }

  .dialog-footer {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
    width: 100%;
  }

  :deep(.table-advanced-filter-dialog) {
    .el-dialog {
      max-width: calc(100vw - 48px);
      margin-bottom: 0;
      border-radius: 20px;
    }

    .el-dialog__body {
      padding-top: 14px;
    }
  }

  @media (width <= 900px) {
    .summary-bar,
    .dialog-hero {
      flex-direction: column;
      align-items: flex-start;
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
  }
</style>
