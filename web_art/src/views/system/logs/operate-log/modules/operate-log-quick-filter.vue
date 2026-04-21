<template>
  <div class="operate-log-quick-filter">
    <ElSelect
      class="field-select"
      :model-value="normalizedValue.field"
      clearable
      filterable
      placeholder="请选择过滤字段"
      @update:model-value="handleFieldChange"
    >
      <ElOption
        v-for="field in fields"
        :key="field.value"
        :label="field.label"
        :value="field.value"
      />
    </ElSelect>

    <ElSelect
      class="operator-select"
      :model-value="normalizedValue.operator"
      :disabled="!normalizedValue.field"
      placeholder="请选择操作符"
      @update:model-value="handleOperatorChange"
    >
      <ElOption
        v-for="option in getOperatorOptions(normalizedValue.field)"
        :key="option.value"
        :label="option.label"
        :value="option.value"
      />
    </ElSelect>

    <ElInput
      v-if="getValueMode(normalizedValue) === 'input'"
      class="value-input"
      :model-value="normalizeInputValue(normalizedValue.value)"
      clearable
      :placeholder="getValuePlaceholder(normalizedValue)"
      @update:model-value="handleValueChange"
    />

    <ElInputNumber
      v-else-if="getValueMode(normalizedValue) === 'number'"
      class="value-input"
      :model-value="normalizeNumberValue(normalizedValue.value)"
      controls-position="right"
      :placeholder="getValuePlaceholder(normalizedValue)"
      @update:model-value="handleValueChange"
    />

    <ElDatePicker
      v-else-if="getValueMode(normalizedValue) === 'date'"
      class="value-input"
      type="datetime"
      :model-value="normalizeInputValue(normalizedValue.value)"
      clearable
      format="YYYY-MM-DD HH:mm:ss"
      value-format="YYYY-MM-DD HH:mm:ss"
      :placeholder="getValuePlaceholder(normalizedValue)"
      @update:model-value="handleValueChange"
    />

    <ElSelect
      v-else-if="getValueMode(normalizedValue) === 'select'"
      class="value-input"
      :model-value="normalizedValue.value"
      clearable
      :filterable="isSpecialSelectField(normalizedValue.field)"
      :placeholder="getValuePlaceholder(normalizedValue)"
      @update:model-value="handleValueChange"
    >
      <ElOption
        v-for="option in getFieldOptions(normalizedValue)"
        :key="option.value"
        :label="option.label"
        :value="option.value"
      />
    </ElSelect>

    <div v-else class="value-placeholder">当前操作符无需填写筛选值</div>

    <ElButton class="clear-button" :disabled="!hasAnyValue" @click="clearFilter">清空</ElButton>
  </div>
</template>

<script setup lang="ts">
  defineOptions({ name: 'OperateLogQuickFilter' })

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
    placeholder?: string
    containsPlaceholder?: string
  }

  interface FilterCondition {
    field?: string
    operator?: FilterOperator
    value?: string | number
  }

  interface Props {
    modelValue?: FilterCondition
    fields?: FilterField[]
  }

  interface Emits {
    (e: 'update:modelValue', value: FilterCondition): void
  }

  const props = withDefaults(defineProps<Props>(), {
    modelValue: () => ({
      field: undefined,
      operator: undefined,
      value: undefined
    }),
    fields: () => []
  })

  const emit = defineEmits<Emits>()

  const operatorOptionsMap: Record<
    FilterFieldType,
    Array<{ label: string; value: FilterOperator }>
  > = {
    text: [
      { label: '包含', value: 'contains' },
      { label: '不包含', value: 'not_contains' },
      { label: '等于', value: 'eq' },
      { label: '不等于', value: 'neq' },
      { label: '为空', value: 'empty' },
      { label: '不为空', value: 'not_empty' }
    ],
    number: [
      { label: '等于', value: 'eq' },
      { label: '不等于', value: 'neq' },
      { label: '大于', value: 'gt' },
      { label: '小于', value: 'lt' },
      { label: '大于等于', value: 'gte' },
      { label: '小于等于', value: 'lte' },
      { label: '为空', value: 'empty' },
      { label: '不为空', value: 'not_empty' }
    ],
    date: [
      { label: '等于', value: 'eq' },
      { label: '大于', value: 'gt' },
      { label: '小于', value: 'lt' },
      { label: '大于等于', value: 'gte' },
      { label: '小于等于', value: 'lte' },
      { label: '为空', value: 'empty' },
      { label: '不为空', value: 'not_empty' }
    ],
    select: [
      { label: '等于', value: 'eq' },
      { label: '不等于', value: 'neq' },
      { label: '为空', value: 'empty' },
      { label: '不为空', value: 'not_empty' }
    ],
    'special-select': [
      { label: '包含', value: 'contains' },
      { label: '不包含', value: 'not_contains' },
      { label: '等于', value: 'eq' },
      { label: '不等于', value: 'neq' },
      { label: '为空', value: 'empty' },
      { label: '不为空', value: 'not_empty' }
    ]
  }

  const createEmptyFilter = (): FilterCondition => ({
    field: undefined,
    operator: undefined,
    value: undefined
  })

  const normalizedValue = computed(() => ({
    field: props.modelValue.field,
    operator: props.modelValue.operator,
    value: props.modelValue.value
  }))

  const hasAnyValue = computed(() =>
    Boolean(
      normalizedValue.value.field ||
      normalizedValue.value.operator ||
      normalizedValue.value.value !== undefined
    )
  )

  const fieldMap = computed(
    () => new Map(props.fields.map((field) => [field.value, field] as const))
  )

  const isValueRequired = (operator?: FilterOperator) =>
    Boolean(operator && !['empty', 'not_empty'].includes(operator))

  const getField = (fieldKey?: string) => {
    if (!fieldKey) return undefined
    return fieldMap.value.get(fieldKey)
  }

  const getOperatorOptions = (fieldKey?: string) => {
    const field = getField(fieldKey)
    if (!field) return []
    return operatorOptionsMap[field.type] || []
  }

  const getDefaultOperator = (fieldKey?: string) => getOperatorOptions(fieldKey)[0]?.value

  const emitValue = (value: FilterCondition) => {
    emit('update:modelValue', {
      field: value.field,
      operator: value.operator,
      value: value.value
    })
  }

  const handleFieldChange = (field?: string) => {
    if (!field) {
      emitValue(createEmptyFilter())
      return
    }

    emitValue({
      field,
      operator: getDefaultOperator(field),
      value: undefined
    })
  }

  const handleOperatorChange = (operator?: FilterOperator) => {
    emitValue({
      field: normalizedValue.value.field,
      operator,
      value: undefined
    })
  }

  const handleValueChange = (value?: string | number) => {
    emitValue({
      ...normalizedValue.value,
      value
    })
  }

  const clearFilter = () => {
    emitValue(createEmptyFilter())
  }

  const isSpecialSelectField = (fieldKey?: string) => getField(fieldKey)?.type === 'special-select'

  const getValueMode = (condition: FilterCondition) => {
    if (!condition.field || !condition.operator || !isValueRequired(condition.operator)) {
      return 'none'
    }

    const field = getField(condition.field)
    if (!field) return 'none'

    if (field.type === 'special-select') {
      return ['contains', 'not_contains'].includes(condition.operator) ? 'input' : 'select'
    }

    if (field.type === 'text') return 'input'
    if (field.type === 'number') return 'number'
    if (field.type === 'date') return 'date'

    return 'select'
  }

  const getFieldOptions = (condition: FilterCondition) => getField(condition.field)?.options || []

  const getValuePlaceholder = (condition: FilterCondition) => {
    const field = getField(condition.field)
    if (!field || !condition.operator) {
      return '请先选择字段和操作符'
    }

    if (
      field.type === 'special-select' &&
      ['contains', 'not_contains'].includes(condition.operator)
    ) {
      return field.containsPlaceholder || `请输入${field.label}`
    }

    if (field.type === 'select' || getValueMode(condition) === 'select') {
      return field.placeholder || `请选择${field.label}`
    }

    if (field.type === 'date') {
      return field.placeholder || `请选择${field.label}`
    }

    return field.placeholder || `请输入${field.label}`
  }

  const normalizeInputValue = (value: FilterCondition['value']) =>
    typeof value === 'number' ? String(value) : value

  const normalizeNumberValue = (value: FilterCondition['value']) => {
    if (typeof value === 'number') return value
    if (typeof value === 'string' && value.trim() !== '' && !Number.isNaN(Number(value))) {
      return Number(value)
    }
    return undefined
  }
</script>

<style lang="scss" scoped>
  .operate-log-quick-filter {
    display: grid;
    grid-template-columns: minmax(150px, 1fr) minmax(140px, 0.9fr) minmax(220px, 1.2fr) auto;
    gap: 10px;
    align-items: center;
    width: 100%;
  }

  .field-select,
  .operator-select,
  .value-input {
    width: 100%;
  }

  .value-placeholder {
    display: flex;
    align-items: center;
    min-height: 32px;
    padding: 0 11px;
    color: var(--el-text-color-placeholder);
    background-color: var(--el-fill-color-light);
    border: 1px dashed var(--el-border-color);
    border-radius: 8px;
  }

  .clear-button {
    height: 34px;
    padding: 0 14px;
    color: var(--el-color-danger);
    background: var(--el-color-danger-light-9);
    border: 1px solid var(--el-color-danger-light-7);
    border-radius: 8px;
    box-shadow: 0 6px 14px rgb(245 108 108 / 10%);
  }

  :deep(.el-input-number) {
    width: 100%;
  }

  @media (width <= 1200px) {
    .operate-log-quick-filter {
      grid-template-columns: 1fr 1fr;
    }
  }

  @media (width <= 900px) {
    .operate-log-quick-filter {
      grid-template-columns: 1fr;
    }
  }
</style>
