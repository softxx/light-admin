<template>
  <ElDialog
    v-model="visible"
    :title="props.dialogType === 'add' ? '新增角色' : '编辑角色'"
    width="640px"
    align-center
    @closed="handleClosed"
  >
    <ElForm ref="formRef" :model="form" :rules="rules" label-width="100px">
      <ElFormItem label="角色名称" prop="name">
        <ElInput v-model="form.name" placeholder="请输入角色名称" />
      </ElFormItem>

      <ElFormItem label="权限标识" prop="role_key">
        <ElInput v-model="form.role_key" placeholder="请输入权限标识" />
      </ElFormItem>

      <ElFormItem label="数据范围" prop="data_range">
        <ElSelect v-model="form.data_range" placeholder="请选择数据范围" style="width: 100%">
          <ElOption
            v-for="option in dataRangeOptions"
            :key="option.value"
            :label="option.label"
            :value="option.value"
          />
        </ElSelect>
      </ElFormItem>

      <ElFormItem v-if="String(form.data_range) === '2'" label="部门团队" prop="departments">
        <ElTreeSelect
          v-model="form.departments"
          :data="departmentOptions"
          multiple
          show-checkbox
          check-strictly
          node-key="id"
          clearable
          filterable
          style="width: 100%"
          placeholder="请选择部门"
          :props="treeProps"
        />
      </ElFormItem>

      <ElFormItem label="备注" prop="note">
        <ElInput v-model="form.note" type="textarea" :rows="4" placeholder="请输入备注" />
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
  import { fetchGetRoleDetail, fetchSaveRole } from '@/api/system-manage'

  type DialogType = 'add' | 'edit'

  interface Props {
    modelValue: boolean
    dialogType: DialogType
    roleData?: Api.SystemManage.RoleListItem
    departmentOptions?: Api.SystemManage.DepartmentOption[]
  }

  interface Emits {
    (e: 'update:modelValue', value: boolean): void
    (e: 'success', type: DialogType): void
  }

  const props = withDefaults(defineProps<Props>(), {
    modelValue: false,
    dialogType: 'add',
    roleData: undefined,
    departmentOptions: () => []
  })

  const emit = defineEmits<Emits>()

  const formRef = ref<FormInstance>()
  const loading = ref(false)
  const submitting = ref(false)

  const visible = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value)
  })

  const form = reactive<Api.SystemManage.RolePayload>({
    id: undefined,
    name: '',
    role_key: '',
    note: '',
    data_range: 1,
    departments: []
  })

  const dataRangeOptions = [
    { label: '全部数据', value: 1 },
    { label: '自定义数据', value: 2 },
    { label: '本人数据', value: 3 },
    { label: '部门数据', value: 4 },
    { label: '部门及以下数据', value: 5 }
  ]

  const treeProps = {
    label: 'name',
    value: 'id',
    children: 'children'
  }

  const rules = reactive<FormRules>({
    name: [{ required: true, message: '请输入角色名称', trigger: 'blur' }],
    role_key: [{ required: true, message: '请输入权限标识', trigger: 'blur' }],
    data_range: [{ required: true, message: '请选择数据范围', trigger: 'change' }],
    departments: [
      {
        validator: (_rule, value, callback) => {
          if (String(form.data_range) === '2' && (!Array.isArray(value) || value.length === 0)) {
            callback(new Error('请选择部门'))
            return
          }
          callback()
        },
        trigger: 'change'
      }
    ]
  })

  const resetForm = () => {
    Object.assign(form, {
      id: undefined,
      name: '',
      role_key: '',
      note: '',
      data_range: 1,
      departments: []
    })
  }

  const fillForm = async () => {
    resetForm()

    if (props.dialogType !== 'edit' || !props.roleData?.id) {
      return
    }

    loading.value = true
    try {
      const detail = await fetchGetRoleDetail(props.roleData.id)
      Object.assign(form, {
        id: detail.id,
        name: detail.name || '',
        role_key: detail.role_key || '',
        note: detail.note || '',
        data_range: Number(detail.data_range || 1),
        departments: Array.isArray(detail.departments) ? detail.departments : []
      })
    } finally {
      loading.value = false
    }
  }

  const handleSubmit = async () => {
    if (!formRef.value || loading.value) {
      return
    }

    await formRef.value.validate()

    submitting.value = true
    try {
      await fetchSaveRole({
        ...form,
        departments: String(form.data_range) === '2' ? form.departments || [] : []
      })
      visible.value = false
      emit('success', props.dialogType)
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
      if (!value) {
        return
      }

      fillForm().then(() => {
        nextTick(() => {
          formRef.value?.clearValidate()
        })
      })
    }
  )
</script>
