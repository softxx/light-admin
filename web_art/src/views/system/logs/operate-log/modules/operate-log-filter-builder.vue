<template>
  <div class="operate-log-filter-builder">
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
            type="datetime"
            :model-value="normalizeInputValue(conditionItem.condition.value)"
            clearable
            format="YYYY-MM-DD HH:mm:ss"
            value-format="YYYY-MM-DD HH:mm:ss"
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
  defineOptions({ name: 'OperateLogFilterBuilder' })

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

  interface FilterGroup {
    conditions?: FilterCondition[]
  }

  interface Props {
    modelValue?: FilterGroup[]
    fields?: FilterField[]
  }

  interface Emits {
    (e: 'update:modelValue', value: FilterGroup[]): void
  }

  const props = withDefaults(defineProps<Props>(), {
    modelValue: () => [],
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

  const isCollapsed = ref(false)

  const createEmptyCondition = (): FilterCondition => ({
    field: undefined,
    operator: undefined,
    value: undefined
  })

  const createEmptyGroup = (): FilterGroup => ({
    conditions: [createEmptyCondition()]
  })

  const cloneCondition = (condition: FilterCondition): FilterCondition => ({
    field: condition.field,
    operator: condition.operator,
    value: condition.value
  })

  const cloneGroup = (group?: FilterGroup): FilterGroup => ({
    conditions:
      group?.conditions && group.conditions.length > 0
        ? group.conditions.map(cloneCondition)
        : [createEmptyCondition()]
  })

  const normalizedGroups = computed(() =>
    props.modelValue.length > 0
      ? props.modelValue.map((group) => cloneGroup(group))
      : [createEmptyGroup()]
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

  const emitGroups = (groups: FilterGroup[]) => {
    emit(
      'update:modelValue',
      groups.map((group) => ({
        conditions: (group.conditions || []).map(cloneCondition)
      }))
    )
  }

  const updateCondition = (
    groupIndex: number,
    conditionIndex: number,
    patch: Partial<FilterCondition>
  ) => {
    const nextGroups = normalizedGroups.value.map((group) => ({
      conditions: (group.conditions || []).map(cloneCondition)
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
      updateCondition(groupIndex, conditionIndex, createEmptyCondition())
      return
    }

    updateCondition(groupIndex, conditionIndex, {
      field,
      operator: getDefaultOperator(field),
      value: undefined
    })
  }

  const handleOperatorChange = (
    groupIndex: number,
    conditionIndex: number,
    operator?: FilterOperator
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
      conditions: (group.conditions || []).map(cloneCondition)
    }))
    nextGroups[groupIndex].conditions = [
      ...(nextGroups[groupIndex].conditions || []),
      createEmptyCondition()
    ]
    emitGroups(nextGroups)
  }

  const removeCondition = (groupIndex: number, conditionIndex: number) => {
    const targetGroup = normalizedGroups.value[groupIndex]
    if (!targetGroup || (targetGroup.conditions?.length || 0) <= 1) {
      return
    }

    const nextGroups = normalizedGroups.value.map((group) => ({
      conditions: (group.conditions || []).map(cloneCondition)
    }))

    nextGroups[groupIndex].conditions = (nextGroups[groupIndex].conditions || []).filter(
      (_, currentIndex) => currentIndex !== conditionIndex
    )

    emitGroups(nextGroups)
  }

  const addGroup = () => {
    isCollapsed.value = false
    emitGroups([...normalizedGroups.value, createEmptyGroup()])
  }

  const removeGroup = (groupIndex: number) => {
    const nextGroups = normalizedGroups.value.filter(
      (_, currentIndex) => currentIndex !== groupIndex
    )
    emitGroups(nextGroups.length > 0 ? nextGroups : [createEmptyGroup()])
  }

  const toggleCollapse = () => {
    isCollapsed.value = !isCollapsed.value
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
  .operate-log-filter-builder {
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
      grid-template-columns: 56px minmax(150px, 1fr) minmax(140px, 0.9fr) minmax(220px, 1.3fr) auto;
      gap: 10px;
      align-items: center;
    }

    .condition-joiner {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      height: 32px;
      color: var(--el-text-color-secondary);
      background: var(--el-fill-color-light);
      border-radius: 8px;
      font-size: 12px;
      font-weight: 600;
    }

    .value-input,
    .field-select,
    .operator-select {
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

    .group-footer {
      display: flex;
      justify-content: flex-start;
    }

    .group-separator {
      display: flex;
      align-items: center;
      gap: 12px;
      color: var(--el-text-color-secondary);
      font-size: 12px;
      font-weight: 600;

      &::before,
      &::after {
        flex: 1;
        height: 1px;
        content: '';
        background: linear-gradient(90deg, rgb(148 163 184 / 0%), rgb(148 163 184 / 50%));
      }
    }

    .action-button {
      height: 34px;
      padding: 0 14px;
      border-radius: 8px;
      font-weight: 500;
      transition:
        transform 0.2s ease,
        box-shadow 0.2s ease,
        background-color 0.2s ease;

      &:not(:disabled):hover {
        transform: translateY(-1px);
      }
    }

    .add-button {
      color: var(--el-color-primary);
      background: var(--el-color-primary-light-9);
      border: 1px solid var(--el-color-primary-light-7);
      box-shadow: 0 6px 14px rgb(64 158 255 / 10%);
    }

    .add-group-button {
      color: #0f766e;
      background: rgb(20 184 166 / 10%);
      border: 1px solid rgb(20 184 166 / 24%);
      box-shadow: 0 6px 14px rgb(20 184 166 / 10%);
    }

    .collapse-button {
      color: var(--el-text-color-regular);
      background: var(--el-fill-color-light);
      border: 1px solid var(--el-border-color);
    }

    .remove-condition-button,
    .remove-group-button {
      color: var(--el-color-danger);
      background: var(--el-color-danger-light-9);
      border: 1px solid var(--el-color-danger-light-7);
      box-shadow: 0 6px 14px rgb(245 108 108 / 10%);
    }

    .remove-condition-button {
      justify-self: end;
    }

    .collapsed-summary {
      padding: 8px 12px;
      color: var(--el-text-color-secondary);
      background: linear-gradient(135deg, rgb(64 158 255 / 6%), rgb(64 158 255 / 2%));
      border: 1px solid var(--el-color-primary-light-8);
      border-radius: 10px;
      font-size: 12px;
    }

    .filter-footer {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
    }

    .footer-actions {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
    }

    .filter-tip {
      color: var(--el-text-color-secondary);
      font-size: 12px;
      line-height: 1;
      white-space: nowrap;
    }
  }

  :deep(.el-input-number) {
    width: 100%;
  }

  @media (width <= 900px) {
    .operate-log-filter-builder {
      .filter-row {
        grid-template-columns: 1fr;
      }

      .condition-joiner,
      .remove-condition-button {
        justify-self: start;
      }

      .group-title-wrap,
      .group-header,
      .filter-footer {
        align-items: flex-start;
      }

      .filter-footer {
        flex-direction: column;
      }

      .filter-tip {
        white-space: normal;
      }
    }
  }
</style>
