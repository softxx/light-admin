<template>
  <TableAdvancedFilter
    v-bind="props"
    @update:modelValue="emit('update:modelValue', $event)"
    @apply="emit('apply', $event)"
  />
</template>

<script setup lang="ts">
  import type { TableFilterFieldSchema, TableFilterGroup } from '@/types'
  import { TableAdvancedFilter } from '@/components/business/table-filters'

  defineOptions({ name: 'OperateLogAdvancedFilter' })

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
</script>
