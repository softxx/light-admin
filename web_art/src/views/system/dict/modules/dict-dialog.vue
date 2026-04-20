<template>
  <ElDialog
    v-model="visible"
    :title="form.id ? '编辑字典' : '新增字典'"
    width="560px"
    align-center
    @closed="handleClosed"
  >
    <ElForm ref="formRef" :model="form" :rules="rules" label-width="90px">
      <ElFormItem label="字典名称" prop="name">
        <ElInput v-model="form.name" placeholder="请输入字典名称" />
      </ElFormItem>

      <ElFormItem label="字典值" prop="value">
        <ElInput v-model="form.value" placeholder="请输入字典值" />
      </ElFormItem>

      <ElFormItem v-if="isDictType" label="组件类型" prop="widget_type">
        <ElSelect v-model="form.widget_type" placeholder="请选择组件类型" style="width: 100%">
          <ElOption label="文本" value="text" />
          <ElOption label="标签" value="tag" />
          <ElOption label="徽标" value="badge" />
        </ElSelect>
      </ElFormItem>

      <ElFormItem v-else label="颜色" prop="color">
        <ElInput v-model="form.color" placeholder="请输入颜色，如 #409EFF" />
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
  import { fetchSaveDict } from '@/api/system-manage'

  interface Props {
    modelValue: boolean
    editData?: Partial<Api.SystemManage.DictListItem> | null
    dictType: string
  }

  interface Emits {
    (e: 'update:modelValue', value: boolean): void
    (e: 'success'): void
  }

  const props = withDefaults(defineProps<Props>(), {
    modelValue: false,
    editData: null
  })

  const emit = defineEmits<Emits>()
  const formRef = ref<FormInstance>()
  const submitting = ref(false)

  const visible = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value)
  })

  const form = reactive<Api.SystemManage.DictPayload>({
    id: undefined,
    type: '',
    name: '',
    value: '',
    note: '',
    color: '',
    widget_type: 'text',
    status: 1
  })

  const isDictType = computed(() => form.type === 'dict_type')

  const rules = reactive<FormRules>({
    name: [{ required: true, message: '请输入字典名称', trigger: 'blur' }],
    value: [{ required: true, message: '请输入字典值', trigger: 'blur' }],
    widget_type: [
      {
        validator: (_rule, value, callback) => {
          if (isDictType.value && !value) {
            callback(new Error('请选择组件类型'))
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
      type: props.dictType,
      name: '',
      value: '',
      note: '',
      color: '',
      widget_type: 'text',
      status: 1
    })
  }

  const fillForm = () => {
    resetForm()
    if (!props.editData) {
      return
    }

    Object.assign(form, {
      id: props.editData.id,
      type: props.editData.type || props.dictType,
      name: props.editData.name || '',
      value: props.editData.value || '',
      note: props.editData.note || '',
      color: props.editData.color || '',
      widget_type: props.editData.widget_type || 'text',
      status: props.editData.status || 1
    })
  }

  const handleSubmit = async () => {
    if (!formRef.value) {
      return
    }

    await formRef.value.validate()

    submitting.value = true
    try {
      await fetchSaveDict({
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
        fillForm()
        nextTick(() => formRef.value?.clearValidate())
      }
    }
  )
</script>
