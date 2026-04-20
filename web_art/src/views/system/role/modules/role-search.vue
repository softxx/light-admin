<template>
  <ArtSearchBar
    ref="searchBarRef"
    v-model="formData"
    :items="formItems"
    :showExpand="false"
    @reset="emit('reset')"
    @search="handleSearch"
  />
</template>

<script setup lang="ts">
  interface Props {
    modelValue: Api.SystemManage.RoleSearchParams
  }

  interface Emits {
    (e: 'update:modelValue', value: Api.SystemManage.RoleSearchParams): void
    (e: 'search', value: Api.SystemManage.RoleSearchParams): void
    (e: 'reset'): void
  }

  const props = defineProps<Props>()
  const emit = defineEmits<Emits>()
  const searchBarRef = ref()

  const formData = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value)
  })

  const formItems = computed(() => [
    {
      label: '关键词',
      key: 'key',
      type: 'input',
      props: {
        clearable: true,
        placeholder: '请输入角色名称或权限标识'
      }
    }
  ])

  const handleSearch = async (params: Api.SystemManage.RoleSearchParams) => {
    await searchBarRef.value?.validate?.()
    emit('search', params)
  }
</script>
