import type {
  TableFilterCondition,
  TableFilterDateType,
  TableFilterFieldSchema,
  TableFilterFormModel,
  TableFilterGroup,
  TableFilterOperator,
  TableFilterOption,
  TableFilterValueMode
} from '@/types'

type TableFilterOperatorOption = {
  label: string
  value: TableFilterOperator
}

const DEFAULT_OPERATOR_MAP: Record<TableFilterFieldSchema['type'], TableFilterOperator[]> = {
  text: ['contains', 'not_contains', 'eq', 'neq', 'empty', 'not_empty'],
  number: ['eq', 'neq', 'gt', 'lt', 'gte', 'lte', 'empty', 'not_empty'],
  date: ['eq', 'gt', 'lt', 'gte', 'lte', 'empty', 'not_empty'],
  select: ['eq', 'neq', 'empty', 'not_empty'],
  'special-select': ['contains', 'not_contains', 'eq', 'neq', 'empty', 'not_empty']
}

export const TABLE_FILTER_OPERATOR_LABEL_MAP: Record<TableFilterOperator, string> = {
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

function getDatePickerFormat(dateType: TableFilterDateType = 'datetime') {
  return dateType === 'date'
    ? { format: 'YYYY-MM-DD', valueFormat: 'YYYY-MM-DD' }
    : { format: 'YYYY-MM-DD HH:mm:ss', valueFormat: 'YYYY-MM-DD HH:mm:ss' }
}

export function createEmptyTableFilterCondition(): TableFilterCondition {
  return {
    field: undefined,
    operator: undefined,
    value: undefined
  }
}

export function createEmptyTableFilterGroup(): TableFilterGroup {
  return {
    conditions: [createEmptyTableFilterCondition()]
  }
}

export function createTableFilterFormModel(): TableFilterFormModel {
  return {
    quickFilter: createEmptyTableFilterCondition(),
    advancedFilters: [createEmptyTableFilterGroup()]
  }
}

export function cloneTableFilterCondition(
  condition: TableFilterCondition = createEmptyTableFilterCondition()
): TableFilterCondition {
  return {
    field: condition.field,
    operator: condition.operator,
    value: condition.value
  }
}

export function cloneTableFilterGroup(
  group: TableFilterGroup = createEmptyTableFilterGroup()
): TableFilterGroup {
  return {
    conditions:
      group.conditions && group.conditions.length > 0
        ? group.conditions.map(cloneTableFilterCondition)
        : [createEmptyTableFilterCondition()]
  }
}

export function cloneTableFilterGroups(groups: TableFilterGroup[] = []): TableFilterGroup[] {
  if (groups.length === 0) {
    return [createEmptyTableFilterGroup()]
  }

  return groups.map((group) => cloneTableFilterGroup(group))
}

export function isTableFilterValueRequired(operator?: TableFilterOperator) {
  return Boolean(operator && !['empty', 'not_empty'].includes(operator))
}

export function hasTableFilterValue(value: unknown) {
  if (Array.isArray(value)) {
    return value.length > 0
  }

  return value !== '' && value !== undefined && value !== null
}

export function getTableFilterFieldMap(fields: TableFilterFieldSchema[]) {
  return new Map(fields.map((field) => [field.value, field] as const))
}

export function getTableFilterField(fields: TableFilterFieldSchema[], fieldKey?: string) {
  if (!fieldKey) {
    return undefined
  }

  return getTableFilterFieldMap(fields).get(fieldKey)
}

function getFieldOperators(field?: TableFilterFieldSchema): TableFilterOperator[] {
  if (!field) {
    return []
  }

  return field.operators?.length ? field.operators : DEFAULT_OPERATOR_MAP[field.type] || []
}

export function getTableFilterOperatorOptions(
  field?: TableFilterFieldSchema
): TableFilterOperatorOption[] {
  return getFieldOperators(field).map((operator) => ({
    label: TABLE_FILTER_OPERATOR_LABEL_MAP[operator],
    value: operator
  }))
}

export function getDefaultTableFilterOperator(field?: TableFilterFieldSchema) {
  return getFieldOperators(field)[0]
}

function findTableFilterOption(
  options: TableFilterOption[] = [],
  value?: string | number
): TableFilterOption | undefined {
  for (const option of options) {
    if (option.value === value) {
      return option
    }

    const childMatch = findTableFilterOption(option.children || [], value)
    if (childMatch) {
      return childMatch
    }
  }

  return undefined
}

export function normalizeTableFilterQuickFilter(
  filter?: TableFilterCondition,
  fields: TableFilterFieldSchema[] = []
) {
  if (!filter?.field || !filter.operator) {
    return null
  }

  const field = getTableFilterField(fields, filter.field)
  if (!field || !getFieldOperators(field).includes(filter.operator)) {
    return null
  }

  if (isTableFilterValueRequired(filter.operator) && !hasTableFilterValue(filter.value)) {
    return null
  }

  return cloneTableFilterCondition(filter)
}

export function normalizeTableFilterGroups(
  groups: TableFilterGroup[] = [],
  fields: TableFilterFieldSchema[] = []
) {
  const fieldMap = getTableFilterFieldMap(fields)

  return groups
    .map((group) => ({
      conditions: (group.conditions || [])
        .map((condition) => cloneTableFilterCondition(condition))
        .filter((condition) => condition.field && condition.operator)
        .filter((condition) => {
          const field = fieldMap.get(condition.field!)
          return Boolean(field && getFieldOperators(field).includes(condition.operator!))
        })
        .filter(
          (condition) =>
            !isTableFilterValueRequired(condition.operator) || hasTableFilterValue(condition.value)
        )
    }))
    .filter((group) => group.conditions.length > 0)
}

export function getTableFilterValueMode(
  condition: TableFilterCondition,
  fields: TableFilterFieldSchema[] = []
): TableFilterValueMode {
  if (!condition.field || !condition.operator || !isTableFilterValueRequired(condition.operator)) {
    return 'none'
  }

  const field = getTableFilterField(fields, condition.field)
  if (!field) {
    return 'none'
  }

  if (field.type === 'special-select') {
    return ['contains', 'not_contains'].includes(condition.operator)
      ? 'input'
      : field.component === 'tree-select'
        ? 'tree-select'
        : 'select'
  }

  if (field.type === 'text') return 'input'
  if (field.type === 'number') return 'number'
  if (field.type === 'date') return 'date'

  return field.component === 'tree-select' ? 'tree-select' : 'select'
}

export function getTableFilterValuePlaceholder(
  condition: TableFilterCondition,
  fields: TableFilterFieldSchema[] = []
) {
  const field = getTableFilterField(fields, condition.field)
  if (!field || !condition.operator) {
    return '请先选择字段和操作符'
  }

  if (field.type === 'special-select' && ['contains', 'not_contains'].includes(condition.operator)) {
    return field.containsPlaceholder || `请输入${field.label}`
  }

  if (getTableFilterValueMode(condition, fields) === 'tree-select') {
    return field.placeholder || `请选择${field.label}`
  }

  if (field.type === 'select' || getTableFilterValueMode(condition, fields) === 'select') {
    return field.placeholder || `请选择${field.label}`
  }

  if (field.type === 'date') {
    return field.placeholder || `请选择${field.label}`
  }

  return field.placeholder || `请输入${field.label}`
}

export function getTableFilterDatePickerProps(
  condition: TableFilterCondition,
  fields: TableFilterFieldSchema[] = []
) {
  const field = getTableFilterField(fields, condition.field)
  const dateType = field?.dateType || 'datetime'
  const { format, valueFormat } = getDatePickerFormat(dateType)

  return {
    type: dateType,
    format,
    valueFormat
  }
}

export function formatTableFilterConditionValue(
  condition: TableFilterCondition,
  fields: TableFilterFieldSchema[] = []
) {
  if (!isTableFilterValueRequired(condition.operator)) {
    return ''
  }

  const field = getTableFilterField(fields, condition.field)
  if (!field) {
    return condition.value ?? ''
  }

  const option = findTableFilterOption(field.options, condition.value)
  if (option) {
    return option.label
  }

  return condition.value ?? ''
}

export function buildTableFilterSummary(
  groups: TableFilterGroup[] = [],
  fields: TableFilterFieldSchema[] = [],
  emptyText = '未设置高级过滤条件'
) {
  const normalizedGroups = normalizeTableFilterGroups(groups, fields)
  if (normalizedGroups.length === 0) {
    return emptyText
  }

  return normalizedGroups
    .map((group) => {
      const conditionText = group.conditions
        .map((condition) => {
          const field = getTableFilterField(fields, condition.field)
          const operatorLabel = condition.operator
            ? TABLE_FILTER_OPERATOR_LABEL_MAP[condition.operator]
            : ''

          if (!field || !operatorLabel) {
            return ''
          }

          const valueText = formatTableFilterConditionValue(condition, fields)
          return valueText === ''
            ? `${field.label} ${operatorLabel}`
            : `${field.label} ${operatorLabel} ${String(valueText)}`
        })
        .filter(Boolean)
        .join(' 且 ')

      return group.conditions.length > 1 ? `(${conditionText})` : conditionText
    })
    .join(' 或 ')
}

export function buildDynamicTableFilterParams(
  payload: Partial<TableFilterFormModel> = {},
  fields: TableFilterFieldSchema[] = []
) {
  const requestParams: Record<string, string> = {}
  const quickFilter = normalizeTableFilterQuickFilter(payload.quickFilter, fields)
  const advancedFilters = normalizeTableFilterGroups(payload.advancedFilters, fields)

  if (quickFilter) {
    requestParams.quick_filter = JSON.stringify(quickFilter)
  }

  if (advancedFilters.length > 0) {
    requestParams.filters = JSON.stringify(advancedFilters)
  }

  return requestParams
}

export function normalizeTableFilterInputValue(value: TableFilterCondition['value']) {
  return typeof value === 'number' ? String(value) : value
}

export function normalizeTableFilterNumberValue(value: TableFilterCondition['value']) {
  if (typeof value === 'number') {
    return value
  }

  if (typeof value === 'string' && value.trim() !== '' && !Number.isNaN(Number(value))) {
    return Number(value)
  }

  return undefined
}
