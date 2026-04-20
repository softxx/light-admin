<template>
  <ElDialog
    v-model="visible"
    :title="form.id ? '编辑部门' : '新增部门'"
    width="620px"
    align-center
    @closed="handleClosed"
  >
    <ElForm ref="formRef" :model="form" :rules="rules" label-width="90px">
      <ElFormItem label="上级部门" prop="parent_id">
        <ElTreeSelect
          v-model="form.parent_id"
          :data="treeData"
          clearable
          filterable
          node-key="id"
          style="width: 100%"
          placeholder="请选择上级部门"
          :props="treeProps"
        />
      </ElFormItem>

      <ElFormItem label="部门名称" prop="name">
        <ElInput v-model="form.name" placeholder="请输入部门名称" />
      </ElFormItem>

      <ElFormItem label="负责人" prop="leader_id">
        <ElSelect v-model="form.leader_id" clearable filterable style="width: 100%" placeholder="请选择负责人">
          <ElOption
            v-for="item in leaderOptions"
            :key="item.id"
            :label="item.realname"
            :value="item.id"
          />
        </ElSelect>
      </ElFormItem>

      <ElFormItem label="排序" prop="sort">
        <ElInputNumber v-model="form.sort" :min="1" :max="9999" style="width: 100%" />
      </ElFormItem>
    </ElForm>

    <template #footer>
      <ElButton @click="visible = false">取消</ElButton>
      <ElButton type="primary" :loading="submitting" @click="handleSubmit">保存</ElButton>
    </template>
  </ElDialog>
</template>

<script setup lang="ts">
  import type { FormInstance, FormRules } from 'element-plus'
  import {
    fetchGetActiveUsers,
    fetchGetDepartmentDetail,
    fetchSaveDepartment
  } from '@/api/system-manage'

  interface Props {
    modelValue: boolean
    editData?: Record<string, any> | null
    treeData?: Api.SystemManage.DepartmentOption[]
  }

  interface Emits {
    (e: 'update:modelValue', value: boolean): void
    (e: 'success'): void
  }

  const props = withDefaults(defineProps<Props>(), {
    modelValue: false,
    editData: null,
    treeData: () => []
  })

  const emit = defineEmits<Emits>()

  const formRef = ref<FormInstance>()
  const submitting = ref(false)
  const leaderOptions = ref<Array<{ id: number | string; realname: string }>>([])

  const visible = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value)
  })

  const treeProps = {
    label: 'name',
    value: 'id',
    children: 'children'
  }

  const form = reactive({
    id: undefined as number | string | undefined,
    parent_id: 0 as number | string,
    name: '',
    leader_id: '' as number | string,
    sort: 1
  })

  const rules = reactive<FormRules>({
    parent_id: [{ required: true, message: '请选择上级部门', trigger: 'change' }],
    name: [{ required: true, message: '请输入部门名称', trigger: 'blur' }]
  })

  const resetForm = () => {
    Object.assign(form, {
      id: undefined,
      parent_id: 0,
      name: '',
      leader_id: '',
      sort: 1
    })
  }

  const loadLeaders = async () => {
    const users = await fetchGetActiveUsers({
      page: 1,
      pageSize: 200
    })

    const records = users.list || users.data || users.records || []
    leaderOptions.value = records.map((item: any) => ({
      id: item.id,
      realname: item.realname
    }))
  }

  const fillForm = async () => {
    resetForm()

    if (!props.editData) {
      return
    }

    if (props.editData.id) {
      const detail = await fetchGetDepartmentDetail(props.editData.id)
      Object.assign(form, {
        id: detail.id,
        parent_id: detail.parent_id ?? 0,
        name: detail.name || '',
        leader_id: detail.leader_id || detail.leader_user?.id || '',
        sort: Number(detail.sort || 1)
      })
      return
    }

    Object.assign(form, {
      parent_id: props.editData.parent_id ?? props.editData.id ?? 0
    })
  }

  const handleSubmit = async () => {
    if (!formRef.value) {
      return
    }

    await formRef.value.validate()

    submitting.value = true
    try {
      await fetchSaveDepartment({
        ...form
      })
      visible.value = false
      emit('success')
    } finally {
      submitting.value = false
    }
  }

  const handleClosed = () => {
    formRef.value?.clearValidate()
    resetForm()
  }

  watch(
    () => props.modelValue,
    (value) => {
      if (value) {
        Promise.all([loadLeaders(), fillForm()]).then(() => {
          nextTick(() => formRef.value?.clearValidate())
        })
      }
    }
  )
</script>
