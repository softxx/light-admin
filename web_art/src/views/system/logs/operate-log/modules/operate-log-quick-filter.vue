<template>
  <TableQuickFilter v-bind="props" @update:modelValue="emit('update:modelValue', $event)" />
</template>

<script setup lang="ts">
  import type { TableFilterCondition, TableFilterFieldSchema } from '@/types'
  import { TableQuickFilter } from '@/components/business/table-filters'

  defineOptions({ name: 'OperateLogQuickFilter' })

  interface Props {
    modelValue?: TableFilterCondition
    fields?: TableFilterFieldSchema[]
  }

  interface Emits {
    (e: 'update:modelValue', value: TableFilterCondition): void
  }

  const props = withDefaults(defineProps<Props>(), {
    modelValue: undefined,
    fields: () => []
  })

  const emit = defineEmits<Emits>()
</script>
