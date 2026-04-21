<template>
  <div class="table-filter-builder">
    <div
      v-for="(groupItem, displayIndex) in displayGroups"
      :key="`group-${groupItem.groupIndex}`"
      class="group-block"
    >
      <div class="filter-group">
        <div class="group-header">
          <div class="group-title-wrap">
            <span class="group-index">组 {{ groupItem.groupIndex + 1 }}</span>
            <span class="group-title">满足以下全部条件</span>
          </div>

          <ElButton
            class="action-button remove-group-button"
            :disabled="normalizedGroups.length === 1"
            @click="removeGroup(groupItem.groupIndex)"
          >
            删除分组
          </ElButton>
        </div>

        <div
          v-for="conditionItem in groupItem.conditions"
          :key="`group-${groupItem.groupIndex}-condition-${conditionItem.conditionIndex}`"
          class="filter-row"
        >
          <div class="condition-joiner">
            {{ conditionItem.conditionIndex === 0 ? '条件' : '且' }}
          </div>

          <ElSelect
            class="field-select"
            :model-value="conditionItem.condition.field"
            clearable
            filterable
            placeholder="请选择过滤字段"
            @update:model-value="
              handleFieldChange(groupItem.groupIndex, conditionItem.conditionIndex, $event)
            "
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
            :model-value="conditionItem.condition.operator"
            :disabled="!conditionItem.condition.field"
            placeholder="请选择操作符"
            @update:model-value="
              handleOperatorChange(groupItem.groupIndex, conditionItem.conditionIndex, $event)
            "
          >
            <ElOption
              v-for="option in getOperatorOptions(conditionItem.condition.field)"
              :key="option.value"
              :label="option.label"
              :value="option.value"
            />
          </ElSelect>

          <ElInput
            v-if="getValueMode(conditionItem.condition) === 'input'"
            class="value-input"
            :model-value="normalizeInputValue(conditionItem.condition.value)"
            clearable
            :placeholder="getValuePlaceholder(conditionItem.condition)"
            @update:model-value="
              handleValueChange(groupItem.groupIndex, conditionItem.conditionIndex, $event)
            "
          />

          <ElInputNumber
            v-else-if="getValueMode(conditionItem.condition) === 'number'"
            class="value-input"
            :model-value="normalizeNumberValue(conditionItem.condition.value)"
            controls-position="right"
            :placeholder="getValuePlaceholder(conditionItem.condition)"
            @update:model-value="
              handleValueChange(groupItem.groupIndex, conditionItem.conditionIndex, $event)
            "
          />

          <ElDatePicker
            v-else-if="getValueMode(conditionItem.condition) === 'date'"
            class="value-input"
            :model-value="normalizeInputValue(conditionItem.condition.value)"
            clearable
            :placeholder="getValuePlaceholder(conditionItem.condition)"
            v-bind="getDatePickerProps(conditionItem.condition)"
            @update:model-value="
              handleValueChange(groupItem.groupIndex, conditionItem.conditionIndex, $event)
            "
          />

          <ElTreeSelect
            v-else-if="getValueMode(conditionItem.condition) === 'tree-select'"
            class="value-input"
            :model-value="conditionItem.condition.value"
            clearable
            filterable
            check-strictly
            default-expand-all
            node-key="value"
            :data="getFieldOptions(conditionItem.condition)"
            :props="treeSelectProps"
            :placeholder="getValuePlaceholder(conditionItem.condition)"
            @update:model-value="
              handleValueChange(groupItem.groupIndex, conditionItem.conditionIndex, $event)
            "
          />

          <ElSelect
            v-else-if="getValueMode(conditionItem.condition) === 'select'"
            class="value-input"
            :model-value="conditionItem.condition.value"
            clearable
            :filterable="isSpecialSelectField(conditionItem.condition.field)"
            :placeholder="getValuePlaceholder(conditionItem.condition)"
            @update:model-value="
              handleValueChange(groupItem.groupIndex, conditionItem.conditionIndex, $event)
            "
          >
            <ElOption
              v-for="option in getFieldOptions(conditionItem.condition)"
              :key="option.value"
              :label="option.label"
              :value="option.value"
            />
          </ElSelect>

          <div v-else class="value-placeholder">当前操作符无需填写筛选值</div>

          <ElButton
            class="action-button remove-condition-button"
            :disabled="(groupItem.group.conditions?.length || 0) === 1"
            @click="removeCondition(groupItem.groupIndex, conditionItem.conditionIndex)"
          >
            删除条件
          </ElButton>
        </div>

        <div class="group-footer">
          <ElButton class="action-button add-button" @click="addCondition(groupItem.groupIndex)">
            添加条件
          </ElButton>
        </div>
      </div>

      <div v-if="displayIndex < displayGroups.length - 1" class="group-separator">
        <span>或</span>
      </div>
    </div>

    <div v-if="isCollapsed && collapsedSummaryText" class="collapsed-summary">
      {{ collapsedSummaryText }}
    </div>

    <div class="filter-footer">
      <div class="footer-actions">
        <ElButton class="action-button add-group-button" @click="addGroup">添加或分组</ElButton>
        <ElButton
          v-if="canToggleCollapse"
          class="action-button collapse-button"
          @click="toggleCollapse"
        >
          {{ isCollapsed ? '展开全部' : '收起条件' }}
        </ElButton>
      </div>
      <span class="filter-tip">组内按“且”，组间按“或”同时生效</span>
    </div>
  </div>
</template>

<script setup lang="ts">
  import type { TableFilterCondition, TableFilterFieldSchema, TableFilterGroup } from '@/types'
  import {
    cloneTableFilterCondition,
    cloneTableFilterGroup,
    createEmptyTableFilterCondition,
    createEmptyTableFilterGroup,
    getDefaultTableFilterOperator,
    getTableFilterDatePickerProps,
    getTableFilterField,
    getTableFilterOperatorOptions,
    getTableFilterValueMode,
    getTableFilterValuePlaceholder,
    normalizeTableFilterInputValue,
    normalizeTableFilterNumberValue
  } from '@/utils/table/filter'

  defineOptions({ name: 'TableFilterBuilder' })

  interface Props {
    modelValue?: TableFilterGroup[]
    fields?: TableFilterFieldSchema[]
  }

  interface Emits {
    (e: 'update:modelValue', value: TableFilterGroup[]): void
  }

  const props = withDefaults(defineProps<Props>(), {
    modelValue: () => [],
    fields: () => []
  })

  const emit = defineEmits<Emits>()

  const treeSelectProps = {
    label: 'label',
    value: 'value',
    children: 'children'
  }

  const isCollapsed = ref(false)

  const normalizedGroups = computed(() =>
    props.modelValue.length > 0
      ? props.modelValue.map((group) => cloneTableFilterGroup(group))
      : [createEmptyTableFilterGroup()]
  )

  const totalConditionCount = computed(() =>
    normalizedGroups.value.reduce((count, group) => count + (group.conditions?.length || 0), 0)
  )

  const canToggleCollapse = computed(
    () => normalizedGroups.value.length > 1 || totalConditionCount.value > 1
  )

  const displayGroups = computed(() => {
    const mappedGroups = normalizedGroups.value.map((group, groupIndex) => ({
      group,
      groupIndex,
      conditions: (group.conditions || []).map((condition, conditionIndex) => ({
        condition,
        conditionIndex
      }))
    }))

    if (!canToggleCollapse.value || !isCollapsed.value) {
      return mappedGroups
    }

    const firstGroup = mappedGroups[0]
    if (!firstGroup) {
      return []
    }

    return [
      {
        ...firstGroup,
        conditions: firstGroup.conditions.slice(0, 1)
      }
    ]
  })

  const hiddenGroupCount = computed(() =>
    isCollapsed.value ? Math.max(0, normalizedGroups.value.length - displayGroups.value.length) : 0
  )

  const hiddenConditionCount = computed(() => {
    if (!isCollapsed.value) {
      return 0
    }

    const visibleCount = displayGroups.value.reduce(
      (count, group) => count + group.conditions.length,
      0
    )
    return Math.max(0, totalConditionCount.value - visibleCount)
  })

  const collapsedSummaryText = computed(() => {
    if (!isCollapsed.value) {
      return ''
    }

    const summaryParts: string[] = []

    if (hiddenGroupCount.value > 0) {
      summaryParts.push(`${hiddenGroupCount.value} 个或分组`)
    }

    if (hiddenConditionCount.value > 0) {
      summaryParts.push(`${hiddenConditionCount.value} 条条件`)
    }

    return summaryParts.length > 0 ? `已收起 ${summaryParts.join('、')}，展开后可继续编辑。` : ''
  })

  watch(
    [() => normalizedGroups.value.length, () => totalConditionCount.value],
    ([groupCount, conditionCount]) => {
      if (groupCount <= 1 && conditionCount <= 1) {
        isCollapsed.value = false
      }
    }
  )

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

  const emitGroups = (groups: TableFilterGroup[]) => {
    emit(
      'update:modelValue',
      groups.map((group) => ({
        conditions: (group.conditions || []).map(cloneTableFilterCondition)
      }))
    )
  }

  const updateCondition = (
    groupIndex: number,
    conditionIndex: number,
    patch: Partial<TableFilterCondition>
  ) => {
    const nextGroups = normalizedGroups.value.map((group) => ({
      conditions: (group.conditions || []).map(cloneTableFilterCondition)
    }))

    const currentCondition = nextGroups[groupIndex]?.conditions?.[conditionIndex]
    if (!currentCondition) {
      return
    }

    nextGroups[groupIndex].conditions![conditionIndex] = {
      ...currentCondition,
      ...patch
    }

    emitGroups(nextGroups)
  }

  const handleFieldChange = (groupIndex: number, conditionIndex: number, field?: string) => {
    if (!field) {
      updateCondition(groupIndex, conditionIndex, createEmptyTableFilterCondition())
      return
    }

    const fieldConfig = getFieldConfig(field)
    updateCondition(groupIndex, conditionIndex, {
      field,
      operator: getDefaultTableFilterOperator(fieldConfig),
      value: undefined
    })
  }

  const handleOperatorChange = (
    groupIndex: number,
    conditionIndex: number,
    operator?: TableFilterCondition['operator']
  ) => {
    updateCondition(groupIndex, conditionIndex, {
      operator,
      value: undefined
    })
  }

  const handleValueChange = (
    groupIndex: number,
    conditionIndex: number,
    value?: string | number
  ) => {
    updateCondition(groupIndex, conditionIndex, { value })
  }

  const addCondition = (groupIndex: number) => {
    isCollapsed.value = false

    const nextGroups = normalizedGroups.value.map((group) => ({
      conditions: (group.conditions || []).map(cloneTableFilterCondition)
    }))

    nextGroups[groupIndex].conditions = [
      ...(nextGroups[groupIndex].conditions || []),
      createEmptyTableFilterCondition()
    ]

    emitGroups(nextGroups)
  }

  const removeCondition = (groupIndex: number, conditionIndex: number) => {
    const targetGroup = normalizedGroups.value[groupIndex]
    if (!targetGroup || (targetGroup.conditions?.length || 0) <= 1) {
      return
    }

    const nextGroups = normalizedGroups.value.map((group) => ({
      conditions: (group.conditions || []).map(cloneTableFilterCondition)
    }))

    nextGroups[groupIndex].conditions = (nextGroups[groupIndex].conditions || []).filter(
      (_, currentIndex) => currentIndex !== conditionIndex
    )

    emitGroups(nextGroups)
  }

  const addGroup = () => {
    isCollapsed.value = false
    emitGroups([...normalizedGroups.value, createEmptyTableFilterGroup()])
  }

  const removeGroup = (groupIndex: number) => {
    const nextGroups = normalizedGroups.value.filter((_, currentIndex) => currentIndex !== groupIndex)
    emitGroups(nextGroups.length > 0 ? nextGroups : [createEmptyTableFilterGroup()])
  }

  const toggleCollapse = () => {
    isCollapsed.value = !isCollapsed.value
  }
</script>

<style lang="scss" scoped>
  .table-filter-builder {
    display: flex;
    flex-direction: column;
    gap: 12px;
    width: 100%;

    .group-block {
      display: flex;
      flex-direction: column;
      gap: 12px;
    }

    .filter-group {
      display: flex;
      flex-direction: column;
      gap: 12px;
      padding: 14px;
      background:
        linear-gradient(180deg, rgb(255 255 255 / 96%), rgb(248 250 252 / 96%)), var(--el-bg-color);
      border: 1px solid var(--el-border-color-lighter);
      border-radius: 14px;
      box-shadow: 0 10px 24px rgb(15 23 42 / 4%);
    }

    .group-header {
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
    }

    .group-title-wrap {
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .group-index {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      min-width: 58px;
      height: 28px;
      padding: 0 10px;
      color: var(--el-color-primary);
      background: var(--el-color-primary-light-9);
      border: 1px solid var(--el-color-primary-light-7);
      border-radius: 999px;
      font-size: 12px;
      font-weight: 600;
    }

    .group-title {
      color: var(--el-text-color-primary);
      font-size: 14px;
      font-weight: 600;
    }

    .filter-row {
      display: grid;
      grid-template-columns:
        62px
        minmax(160px, 1.05fr)
        minmax(150px, 0.95fr)
        minmax(240px, 1.35fr)
        auto;
      gap: 12px;
      align-items: center;
    }

    .condition-joiner {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      height: 34px;
      color: var(--el-text-color-secondary);
      background: var(--el-fill-color-light);
      border: 1px solid var(--el-border-color-lighter);
      border-radius: 999px;
      font-size: 12px;
      font-weight: 600;
    }

    .field-select,
    .operator-select,
    .value-input {
      width: 100%;
    }

    .value-placeholder {
      display: flex;
      align-items: center;
      min-height: 34px;
      padding: 0 12px;
      color: var(--el-text-color-placeholder);
      background: var(--el-fill-color-light);
      border: 1px dashed var(--el-border-color);
      border-radius: 10px;
      font-size: 13px;
    }

    .group-footer,
    .filter-footer {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      flex-wrap: wrap;
    }

    .footer-actions {
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
    }

    .action-button {
      height: 34px;
      padding: 0 14px;
      border-radius: 10px;
    }

    .add-button,
    .add-group-button {
      color: var(--el-color-primary);
      background: var(--el-color-primary-light-9);
      border-color: var(--el-color-primary-light-7);
    }

    .collapse-button {
      color: var(--el-color-warning);
      background: var(--el-color-warning-light-9);
      border-color: var(--el-color-warning-light-7);
    }

    .remove-group-button,
    .remove-condition-button {
      color: var(--el-color-danger);
      background: var(--el-color-danger-light-9);
      border-color: var(--el-color-danger-light-7);
    }

    .group-separator {
      display: flex;
      justify-content: center;

      span {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 44px;
        height: 28px;
        padding: 0 12px;
        color: #0f766e;
        background: rgb(20 184 166 / 10%);
        border: 1px solid rgb(20 184 166 / 18%);
        border-radius: 999px;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: 0.04em;
      }
    }

    .collapsed-summary {
      padding: 12px 14px;
      color: var(--el-text-color-secondary);
      background: var(--el-fill-color-light);
      border: 1px dashed var(--el-border-color);
      border-radius: 12px;
      font-size: 13px;
      line-height: 1.6;
    }

    .filter-tip {
      color: var(--el-text-color-secondary);
      font-size: 12px;
    }
  }

  :deep(.el-input-number) {
    width: 100%;
  }

  :deep(.el-tree-select) {
    width: 100%;
  }

  @media (width <= 1080px) {
    .table-filter-builder {
      .filter-row {
        grid-template-columns: 1fr 1fr;
      }

      .condition-joiner,
      .remove-condition-button {
        grid-column: span 2;
      }
    }
  }

  @media (width <= 768px) {
    .table-filter-builder {
      .filter-row {
        grid-template-columns: 1fr;
      }

      .condition-joiner,
      .remove-condition-button {
        grid-column: auto;
      }

      .group-footer,
      .filter-footer,
      .footer-actions {
        width: 100%;
      }

      .footer-actions :deep(.el-button),
      .group-footer :deep(.el-button) {
        flex: 1;
      }
    }
  }
</style>
