<template>
  <ArtSearchBar
    ref="searchBarRef"
    v-model="formData"
    :items="formItems"
    @reset="emit('reset')"
    @search="handleSearch"
  />
</template>

<script setup lang="ts">
  interface Props {
    modelValue: Api.SystemManage.UserSearchParams
    roleOptions?: Api.SystemManage.RoleOption[]
    departmentOptions?: Api.SystemManage.DepartmentOption[]
  }

  interface Emits {
    (e: 'update:modelValue', value: Api.SystemManage.UserSearchParams): void
    (e: 'search', value: Api.SystemManage.UserSearchParams): void
    (e: 'reset'): void
  }

  const props = withDefaults(defineProps<Props>(), {
    roleOptions: () => [],
    departmentOptions: () => []
  })

  const emit = defineEmits<Emits>()
  const searchBarRef = ref()

  const formData = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value)
  })

  const roleSelectOptions = computed(() =>
    props.roleOptions.map((item) => ({
      label: item.name,
      value: item.id
    }))
  )

  const formItems = computed(() => [
    {
      label: '关键词',
      key: 'key',
      type: 'input',
      props: {
        clearable: true,
        placeholder: '请输入用户名、姓名或拼音'
      }
    },
    {
      label: '角色',
      key: 'roles',
      type: 'select',
      props: {
        clearable: true,
        placeholder: '请选择角色',
        options: roleSelectOptions.value
      }
    },
    {
      label: '用户状态',
      key: 'status',
      type: 'select',
      props: {
        clearable: true,
        placeholder: '请选择状态',
        options: [
          { label: '激活', value: 1 },
          { label: '禁用', value: 2 }
        ]
      }
    },
    {
      label: '部门',
      key: 'dept_id',
      type: 'treeselect',
      props: {
        data: props.departmentOptions,
        clearable: true,
        filterable: true,
        nodeKey: 'id',
        placeholder: '请选择部门',
        props: {
          label: 'name',
          value: 'id',
          children: 'children'
        }
      }
    },
    {
      label: '添加时间',
      key: 'create_time',
      type: 'daterange',
      props: {
        type: 'daterange',
        clearable: true,
        style: { width: '100%' },
        startPlaceholder: '开始日期',
        endPlaceholder: '结束日期',
        rangeSeparator: '至',
        valueFormat: 'YYYY-MM-DD'
      }
    }
  ])

  const handleSearch = async (params: Api.SystemManage.UserSearchParams) => {
    await searchBarRef.value?.validate?.()
    emit('search', params)
  }
</script>
