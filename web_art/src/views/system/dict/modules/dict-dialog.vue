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
        <div class="dict-color-field">
          <div class="dict-color-field__input">
            <ElInput
              v-model="form.color"
              clearable
              placeholder="请输入颜色，如 green、blue 或 #409EFF"
            />
            <ElColorPicker
              v-model="pickerColor"
              :predefine="DICT_COLOR_PICKER_PRESETS"
              color-format="hex"
              @change="handlePickerChange"
            />
          </div>

          <div class="dict-color-field__presets">
            <ElTag
              v-for="preset in DICT_COLOR_PRESETS"
              :key="preset.value"
              class="dict-color-field__preset"
              :effect="isColorPresetActive(preset.value) ? 'dark' : 'light'"
              v-bind="resolveDictTagColorProps(preset.value)"
              @click="selectColorPreset(preset.value)"
            >
              {{ preset.label }}
            </ElTag>
          </div>

          <div class="dict-color-field__hint">
            支持手工输入 primary/success/warning/danger/info、blue/green/red/yellow/orange/gray/grey
            或 #RGB/#RRGGBB。
          </div>
        </div>
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
  import {
    DICT_COLOR_PICKER_PRESETS,
    DICT_COLOR_PRESETS,
    isValidDictColor,
    normalizeDictColor,
    resolveDictColorPickerValue,
    resolveDictTagColorProps
  } from '@/utils/dict/tag-color'

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
  const pickerColor = ref(resolveDictColorPickerValue())

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
    ],
    color: [
      {
        validator: (_rule, value, callback) => {
          if (!isDictType.value && !isValidDictColor(value)) {
            callback(new Error('颜色仅支持语义色或 #RGB/#RRGGBB 格式'))
            return
          }

          callback()
        },
        trigger: ['blur', 'change']
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
        ...form,
        color: normalizeDictColor(form.color)
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

  const handlePickerChange = (value: string | null) => {
    form.color = value || ''
  }

  const selectColorPreset = (value: string) => {
    form.color = value
  }

  const isColorPresetActive = (value: string) => {
    return normalizeDictColor(form.color).toLowerCase() === value
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

  watch(
    () => form.color,
    (value) => {
      pickerColor.value = resolveDictColorPickerValue(value)
    }
  )
</script>

<style scoped>
  .dict-color-field {
    width: 100%;
  }

  .dict-color-field__input {
    display: flex;
    align-items: center;
    gap: 10px;
  }

  .dict-color-field__input :deep(.el-input) {
    flex: 1;
  }

  .dict-color-field__presets {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 8px;
  }

  .dict-color-field__preset {
    cursor: pointer;
    user-select: none;
  }

  .dict-color-field__hint {
    margin-top: 6px;
    font-size: 12px;
    line-height: 1.5;
    color: var(--art-gray-500);
  }
</style>
