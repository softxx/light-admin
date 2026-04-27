<template>
  <div class="table-quick-filter">
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
      :model-value="normalizeInputValue(normalizedValue.value)"
      clearable
      :placeholder="getValuePlaceholder(normalizedValue)"
      v-bind="getDatePickerProps(normalizedValue)"
      @update:model-value="handleValueChange"
    />

    <ElTreeSelect
      v-else-if="getValueMode(normalizedValue) === 'tree-select'"
      class="value-input"
      :model-value="normalizedValue.value"
      clearable
      filterable
      check-strictly
      default-expand-all
      node-key="value"
      :data="getFieldOptions(normalizedValue)"
      :props="treeSelectProps"
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
  import type { TableFilterCondition, TableFilterFieldSchema } from '@/types'
  import {
    createEmptyTableFilterCondition,
    getDefaultTableFilterOperator,
    getTableFilterDatePickerProps,
    getTableFilterField,
    getTableFilterOperatorOptions,
    getTableFilterValueMode,
    getTableFilterValuePlaceholder,
    hasTableFilterValue,
    normalizeTableFilterInputValue,
    normalizeTableFilterNumberValue
  } from '@/utils/table/filter'

  defineOptions({ name: 'TableQuickFilter' })

  interface Props {
    modelValue?: TableFilterCondition
    fields?: TableFilterFieldSchema[]
  }

  interface Emits {
    (e: 'update:modelValue', value: TableFilterCondition): void
  }

  const props = withDefaults(defineProps<Props>(), {
    modelValue: () => createEmptyTableFilterCondition(),
    fields: () => []
  })

  const emit = defineEmits<Emits>()

  // The tree select fields reuse the same option schema as flat selects.
  const treeSelectProps = {
    label: 'label',
    value: 'value',
    children: 'children'
  }

  const normalizedValue = computed(() => ({
    field: props.modelValue.field,
    operator: props.modelValue.operator,
    value: props.modelValue.value
  }))

  const hasAnyValue = computed(() =>
    Boolean(
      normalizedValue.value.field ||
        normalizedValue.value.operator ||
        hasTableFilterValue(normalizedValue.value.value)
    )
  )

  const emitValue = (value: TableFilterCondition) => {
    emit('update:modelValue', {
      field: value.field,
      operator: value.operator,
      value: value.value
    })
  }

  const getFieldConfig = (fieldKey?: string) => getTableFilterField(props.fields, fieldKey)

  const getOperatorOptions = (fieldKey?: string) =>
    getTableFilterOperatorOptions(getFieldConfig(fieldKey))

  const getValueMode = (condition: TableFilterCondition) =>
    getTableFilterValueMode(condition, props.fields)

  const getValuePlaceholder = (condition: TableFilterCondition) =>
    getTableFilterValuePlaceholder(condition, props.fields)

  const getDatePickerProps = (condition: TableFilterCondition) =>
    getTableFilterDatePickerProps(condition, props.fields)

  const getFieldOptions = (condition: TableFilterCondition) =>
    getFieldConfig(condition.field)?.options || []

  const isSpecialSelectField = (fieldKey?: string) => getFieldConfig(fieldKey)?.type === 'special-select'

  const normalizeInputValue = (value: TableFilterCondition['value']) =>
    normalizeTableFilterInputValue(value)

  const normalizeNumberValue = (value: TableFilterCondition['value']) =>
    normalizeTableFilterNumberValue(value)

  const handleFieldChange = (field?: string) => {
    if (!field) {
      emitValue(createEmptyTableFilterCondition())
      return
    }

    const fieldConfig = getFieldConfig(field)
    emitValue({
      field,
      operator: getDefaultTableFilterOperator(fieldConfig),
      value: undefined
    })
  }

  const handleOperatorChange = (operator?: TableFilterCondition['operator']) => {
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
    emitValue(createEmptyTableFilterCondition())
  }
</script>

<style lang="scss" scoped>
  .table-quick-filter {
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

  :deep(.el-tree-select) {
    width: 100%;
  }

  @media (width <= 900px) {
    .table-quick-filter {
      grid-template-columns: 1fr 1fr;
    }
  }

  @media (width <= 768px) {
    .table-quick-filter {
      grid-template-columns: 1fr;
    }
  }
</style>
