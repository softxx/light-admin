/**
 * Shared table filter protocol.
 *
 * The same shape is used by the reusable filter components, the page adapters,
 * and the request serializer so each list page can opt in without custom glue.
 */

export type TableFilterFieldType =
  | 'text'
  | 'number'
  | 'date'
  | 'select'
  | 'special-select'

export type TableFilterFieldComponent = 'select' | 'tree-select'

export type TableFilterDateType = 'date' | 'datetime'

export type TableFilterOperator =
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

export type TableFilterValueMode =
  | 'input'
  | 'number'
  | 'date'
  | 'select'
  | 'tree-select'
  | 'none'

export interface TableFilterOption {
  label: string
  value: string | number
  children?: TableFilterOption[]
}

export interface TableFilterFieldSchema {
  label: string
  value: string
  type: TableFilterFieldType
  component?: TableFilterFieldComponent
  dateType?: TableFilterDateType
  options?: TableFilterOption[]
  placeholder?: string
  containsPlaceholder?: string
  operators?: TableFilterOperator[]
}

export interface TableFilterCondition {
  field?: string
  operator?: TableFilterOperator
  value?: string | number
}

export interface TableFilterGroup {
  conditions?: TableFilterCondition[]
}

export interface TableFilterFormModel {
  quickFilter: TableFilterCondition
  advancedFilters: TableFilterGroup[]
}
