<template>
  <ArtSearchBar
    ref="searchBarRef"
    v-model="formData"
    :items="formItems"
    :button-left-limit="0"
    :show-expand="false"
    @reset="emit('reset')"
    @search="handleSearch"
  />
</template>

<script setup lang="ts">
  import { markRaw } from 'vue'
  import type { TableFilterFormModel, TableFilterGroup } from '@/types'
  import { TableAdvancedFilter, TableQuickFilter } from '@/components/business/table-filters'
  import { createUserFilterFields } from './user-filter-fields'

  interface Props {
    modelValue: TableFilterFormModel
    roleOptions?: Api.SystemManage.RoleOption[]
    departmentOptions?: Api.SystemManage.DepartmentOption[]
  }

  interface Emits {
    (e: 'update:modelValue', value: TableFilterFormModel): void
    (e: 'search', value: TableFilterFormModel): void
    (e: 'reset'): void
  }

  const props = withDefaults(defineProps<Props>(), {
    roleOptions: () => [],
    departmentOptions: () => []
  })

  const emit = defineEmits<Emits>()
  const searchBarRef = ref()

  const quickFilterRenderer = markRaw(TableQuickFilter)
  const advancedFilterRenderer = markRaw(TableAdvancedFilter)

  const formData = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value)
  })

  const filterFields = computed(() =>
    createUserFilterFields(props.roleOptions, props.departmentOptions)
  )

  const formItems = computed(() => [
    {
      label: '快捷过滤',
      labelWidth: '84px',
      key: 'quickFilter',
      span: 12,
      render: quickFilterRenderer,
      props: {
        fields: filterFields.value
      }
    },
    {
      label: '高级过滤',
      labelWidth: '84px',
      key: 'advancedFilters',
      span: 6,
      render: advancedFilterRenderer,
      props: {
        fields: filterFields.value,
        onApply: handleAdvancedFilterApply
      }
    }
  ])

  const handleSearch = async (params: Record<string, any>) => {
    await searchBarRef.value?.validate?.()
    emit('search', params as TableFilterFormModel)
  }

  const handleAdvancedFilterApply = (advancedFilters: TableFilterGroup[]) => {
    emit('search', {
      ...formData.value,
      advancedFilters
    })
  }
</script>
