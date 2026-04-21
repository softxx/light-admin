<template>
  <div class="operate-log-advanced-filter">
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
      class="operate-log-advanced-filter-dialog"
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
          <OperateLogFilterBuilder v-model="draftFilters" :fields="fields" />
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
  import OperateLogFilterBuilder from './operate-log-filter-builder.vue'

  defineOptions({ name: 'OperateLogAdvancedFilter' })

  type FilterFieldType = 'text' | 'number' | 'date' | 'select' | 'special-select'
  type FilterOperator =
    | 'contains'
    | 'not_contains'
    | 'eq'
    | 'neq'
    | 'gt'
    | 'lt'
    | 'gte'
    | 'lte'
    | 'empty'
    | 'not_empty'

  interface FilterOption {
    label: string
    value: string | number
  }

  interface FilterField {
    label: string
    value: string
    type: FilterFieldType
    options?: FilterOption[]
  }

  interface FilterCondition {
    field?: string
    operator?: FilterOperator
    value?: string | number
  }

  interface FilterGroup {
    conditions?: FilterCondition[]
  }

  interface Props {
    modelValue?: FilterGroup[]
    fields?: FilterField[]
  }

  interface Emits {
    (e: 'update:modelValue', value: FilterGroup[]): void
    (e: 'apply', value: FilterGroup[]): void
  }

  const props = withDefaults(defineProps<Props>(), {
    modelValue: () => [],
    fields: () => []
  })

  const emit = defineEmits<Emits>()

  const operatorLabelMap: Record<FilterOperator, string> = {
    contains: '包含',
    not_contains: '不包含',
    eq: '等于',
    neq: '不等于',
    gt: '大于',
    lt: '小于',
    gte: '大于等于',
    lte: '小于等于',
    empty: '为空',
    not_empty: '不为空'
  }

  const dialogVisible = ref(false)
  const draftFilters = ref<FilterGroup[]>([])

  const createEmptyCondition = (): FilterCondition => ({
    field: undefined,
    operator: undefined,
    value: undefined
  })

  const createEmptyGroup = (): FilterGroup => ({
    conditions: [createEmptyCondition()]
  })

  const cloneFilters = (groups: FilterGroup[] = []) => {
    if (groups.length === 0) {
      return [createEmptyGroup()]
    }

    return groups.map((group) => ({
      conditions:
        group.conditions && group.conditions.length > 0
          ? group.conditions.map((condition) => ({
              field: condition.field,
              operator: condition.operator,
              value: condition.value
            }))
          : [createEmptyCondition()]
    }))
  }

  const fieldMap = computed(
    () => new Map(props.fields.map((field) => [field.value, field] as const))
  )

  const isValueRequired = (operator?: FilterOperator) =>
    Boolean(operator && !['empty', 'not_empty'].includes(operator))

  const hasFilterValue = (value: unknown) => value !== '' && value !== undefined && value !== null

  const normalizeGroups = (groups: FilterGroup[] = []) =>
    groups
      .map((group) => ({
        conditions: (group.conditions || [])
          .map((condition) => ({
            field: condition.field,
            operator: condition.operator,
            value: condition.value
          }))
          .filter((condition) => condition.field && condition.operator)
          .filter(
            (condition) => !isValueRequired(condition.operator) || hasFilterValue(condition.value)
          )
      }))
      .filter((group) => group.conditions.length > 0)

  const normalizedGroups = computed(() => normalizeGroups(props.modelValue))
  const normalizedDraftGroups = computed(() => normalizeGroups(draftFilters.value))

  const activeConditionCount = computed(() =>
    normalizedGroups.value.reduce((count, group) => count + group.conditions.length, 0)
  )
  const hasActiveFilters = computed(() => activeConditionCount.value > 0)

  const draftGroupCount = computed(() => normalizedDraftGroups.value.length)
  const draftConditionCount = computed(() =>
    normalizedDraftGroups.value.reduce((count, group) => count + group.conditions.length, 0)
  )

  const formatConditionValue = (field: FilterField, condition: FilterCondition) => {
    if (!isValueRequired(condition.operator)) {
      return ''
    }

    if (field.type === 'select') {
      const optionLabel = field.options?.find((option) => option.value === condition.value)?.label
      return optionLabel ?? condition.value ?? ''
    }

    if (field.type === 'special-select' && ['eq', 'neq'].includes(condition.operator || '')) {
      const optionLabel = field.options?.find((option) => option.value === condition.value)?.label
      return optionLabel ?? condition.value ?? ''
    }

    return condition.value ?? ''
  }

  const summaryText = computed(() => {
    if (!hasActiveFilters.value) {
      return '未设置高级过滤条件'
    }

    return normalizedGroups.value
      .map((group) => {
        const conditionText = group.conditions
          .map((condition) => {
            const field = fieldMap.value.get(condition.field!)
            const operatorLabel = condition.operator ? operatorLabelMap[condition.operator] : ''
            if (!field || !operatorLabel) {
              return ''
            }

            const valueText = formatConditionValue(field, condition)
            const hasValueText = valueText !== ''
            return hasValueText
              ? `${field.label} ${operatorLabel} ${String(valueText)}`
              : `${field.label} ${operatorLabel}`
          })
          .filter(Boolean)
          .join(' 且 ')

        return group.conditions.length > 1 ? `（${conditionText}）` : conditionText
      })
      .join(' 或 ')
  })

  const emitFilters = (groups: FilterGroup[]) => {
    const nextFilters = cloneFilters(groups)
    emit('update:modelValue', nextFilters)
    return nextFilters
  }

  const openDialog = () => {
    draftFilters.value = cloneFilters(props.modelValue)
    dialogVisible.value = true
  }

  const clearFilters = () => {
    const nextFilters = emitFilters([createEmptyGroup()])
    emit('apply', nextFilters)
  }

  const resetDraft = () => {
    draftFilters.value = [createEmptyGroup()]
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
  .operate-log-advanced-filter {
    width: 100%;
  }

  .summary-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    min-height: 32px;
    padding: 0 10px 0 12px;
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

  :deep(.operate-log-advanced-filter-dialog) {
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
      align-items: flex-start;
      flex-direction: column;
    }

    .summary-bar {
      padding: 10px 12px;
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
