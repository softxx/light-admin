<template>
  <TableFilterBuilder v-bind="props" @update:modelValue="emit('update:modelValue', $event)" />
</template>

<script setup lang="ts">
  import type { TableFilterFieldSchema, TableFilterGroup } from '@/types'
  import { TableFilterBuilder } from '@/components/business/table-filters'

  defineOptions({ name: 'OperateLogFilterBuilder' })

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
</script>
