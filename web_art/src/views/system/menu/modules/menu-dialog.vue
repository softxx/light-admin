<template>
  <ElDialog
    v-model="visible"
    :title="form.id ? '编辑菜单' : '新增菜单'"
    class="menu-dialog"
    width="min(760px, calc(100vw - 24px))"
    align-center
    @closed="handleClosed"
  >
    <ElForm ref="formRef" class="menu-form" :model="form" :rules="rules" label-width="100px">
      <ElRow :gutter="16">
        <ElCol :span="24">
          <ElFormItem label="上级菜单" prop="pid">
            <ElTreeSelect
              v-model="form.pid"
              :data="treeData"
              clearable
              filterable
              node-key="id"
              style="width: 100%"
              placeholder="请选择上级菜单"
              :props="treeProps"
            />
          </ElFormItem>
        </ElCol>

        <ElCol :span="24">
          <ElFormItem label="菜单类型" prop="type">
            <ElRadioGroup v-model="form.type">
              <ElRadio :value="0">目录</ElRadio>
              <ElRadio :value="1">菜单</ElRadio>
              <ElRadio :value="2">权限</ElRadio>
            </ElRadioGroup>
          </ElFormItem>
        </ElCol>

        <ElCol v-if="form.type !== 2" :span="24">
          <ElFormItem label="打开方式" prop="open_type">
            <ElRadioGroup v-model="form.open_type" :disabled="form.type !== 1">
              <ElRadio :value="0">组件</ElRadio>
              <ElRadio :value="1">内链</ElRadio>
              <ElRadio :value="2">外链</ElRadio>
            </ElRadioGroup>
          </ElFormItem>
        </ElCol>

        <ElCol :xs="24" :sm="12">
          <ElFormItem label="菜单名称" prop="title">
            <ElInput v-model="form.title" placeholder="请输入菜单名称" />
          </ElFormItem>
        </ElCol>

        <ElCol v-if="form.type !== 2" :xs="24" :sm="12">
          <ElFormItem label="路由地址" prop="path">
            <ElInput
              v-model="form.path"
              :placeholder="form.open_type === 2 ? '请输入外链地址' : '请输入路由地址'"
            />
          </ElFormItem>
        </ElCol>

        <ElCol v-if="form.type !== 2 && form.open_type === 0" :xs="24" :sm="12">
          <ElFormItem label="路由组件" prop="component">
            <ElInput v-model="form.component" placeholder="请输入路由组件" />
          </ElFormItem>
        </ElCol>

        <ElCol v-if="form.type === 2" :xs="24" :sm="12">
          <ElFormItem label="权限节点" prop="rules">
            <ElInput v-model="form.rules" placeholder="如：system:user:update" />
          </ElFormItem>
        </ElCol>

        <ElCol v-if="form.type !== 2 && form.open_type === 1" :xs="24" :sm="12">
          <ElFormItem label="内链地址" prop="link_url">
            <ElInput v-model="form.link_url" placeholder="请输入内链地址" />
          </ElFormItem>
        </ElCol>

        <ElCol v-if="form.type !== 2" :xs="24" :sm="12">
          <ElFormItem label="菜单图标" prop="icon">
            <ElInput v-model="form.icon" placeholder="请输入图标名称" />
          </ElFormItem>
        </ElCol>

        <ElCol v-if="form.type !== 2 && form.open_type === 0" :xs="24" :sm="12">
          <ElFormItem label="高亮导航" prop="active_key">
            <ElInput v-model="form.active_key" placeholder="请输入高亮导航路径" />
          </ElFormItem>
        </ElCol>

        <ElCol :xs="24" :sm="12">
          <ElFormItem label="排序" prop="sort">
            <ElInputNumber v-model="sortValue" :min="1" :max="9999" style="width: 100%" />
          </ElFormItem>
        </ElCol>

        <ElCol v-if="form.type !== 2" :xs="24" :sm="12">
          <ElFormItem label="是否显示" prop="hidden">
            <ElRadioGroup v-model="form.hidden">
              <ElRadio :value="0">显示</ElRadio>
              <ElRadio :value="1">隐藏</ElRadio>
            </ElRadioGroup>
          </ElFormItem>
        </ElCol>

        <ElCol v-if="form.type !== 2 && form.open_type === 0" :xs="24" :sm="12">
          <ElFormItem label="隐藏子菜单" prop="hide_children">
            <ElRadioGroup v-model="form.hide_children">
              <ElRadio :value="0">否</ElRadio>
              <ElRadio :value="1">是</ElRadio>
            </ElRadioGroup>
          </ElFormItem>
        </ElCol>
      </ElRow>
    </ElForm>

    <template #footer>
      <ElButton @click="visible = false">取消</ElButton>
      <ElButton type="primary" :loading="submitting" @click="handleSubmit">保存</ElButton>
    </template>
  </ElDialog>
</template>

<script setup lang="ts">
  import type { FormInstance, FormRules } from 'element-plus'
  import { fetchSaveMenu } from '@/api/system-manage'

  interface TreeNode extends Api.SystemManage.MenuListItem {
    children?: TreeNode[]
  }

  interface Props {
    modelValue: boolean
    editData?: Partial<Api.SystemManage.MenuListItem> | null
    treeData?: TreeNode[]
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

  const visible = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value)
  })

  const treeProps = {
    label: 'title',
    value: 'id',
    children: 'children'
  }

  const form = reactive<Api.SystemManage.MenuPayload>({
    id: undefined,
    pid: 0,
    title: '',
    path: '',
    component: '',
    icon: '',
    rules: '',
    sort: 1,
    type: 0,
    hidden: 0,
    hide_children: 0,
    active_key: '',
    open_type: 0,
    link_url: ''
  })

  const sortValue = computed({
    get: () => Number(form.sort ?? 1),
    set: (value: number | null | undefined) => {
      form.sort = value ?? 1
    }
  })

  const rules = reactive<FormRules>({
    pid: [{ required: true, message: '请选择上级菜单', trigger: 'change' }],
    title: [{ required: true, message: '请输入菜单名称', trigger: 'blur' }],
    path: [
      {
        validator: (_rule, value, callback) => {
          if (Number(form.type) === 2) {
            callback()
            return
          }

          if (!value) {
            callback(new Error('请输入路由地址'))
            return
          }

          if (Number(form.open_type) === 2) {
            try {
              new URL(value)
            } catch {
              callback(new Error('请输入合法的外链地址'))
              return
            }
          }

          callback()
        },
        trigger: 'blur'
      }
    ],
    component: [
      {
        validator: (_rule, value, callback) => {
          if (Number(form.type) !== 2 && Number(form.open_type) === 0 && !value) {
            callback(new Error('请输入路由组件'))
            return
          }
          callback()
        },
        trigger: 'blur'
      }
    ],
    rules: [
      {
        validator: (_rule, value, callback) => {
          if (Number(form.type) === 2 && !value) {
            callback(new Error('请输入权限节点'))
            return
          }
          callback()
        },
        trigger: 'blur'
      }
    ],
    link_url: [
      {
        validator: (_rule, value, callback) => {
          if (Number(form.type) !== 2 && Number(form.open_type) === 1 && !value) {
            callback(new Error('请输入内链地址'))
            return
          }
          callback()
        },
        trigger: 'blur'
      }
    ]
  })

  const resetForm = () => {
    Object.assign(form, {
      id: undefined,
      pid: 0,
      title: '',
      path: '',
      component: '',
      icon: '',
      rules: '',
      sort: 1,
      type: 0,
      hidden: 0,
      hide_children: 0,
      active_key: '',
      open_type: 0,
      link_url: ''
    })
  }

  const fillForm = () => {
    resetForm()

    if (!props.editData) {
      return
    }

    Object.assign(form, {
      id: props.editData.id,
      pid: props.editData.pid ?? props.editData.id ?? 0,
      title: props.editData.title || '',
      path: props.editData.path || '',
      component: props.editData.component || '',
      icon: props.editData.icon || '',
      rules: props.editData.rules || '',
      sort: Number(props.editData.sort || 1),
      type: Number(props.editData.type ?? 0),
      hidden: Number(props.editData.hidden ?? 0),
      hide_children: Number(props.editData.hide_children ?? 0),
      active_key: props.editData.active_key || '',
      open_type: Number(props.editData.open_type ?? 0),
      link_url: props.editData.link_url || ''
    })
  }

  watch(
    () => form.type,
    (value) => {
      if (Number(value) !== 1) {
        form.open_type = 0
      }
      if (Number(value) === 2) {
        form.component = ''
        form.icon = ''
        form.active_key = ''
        form.link_url = ''
      }
    }
  )

  watch(
    () => form.open_type,
    (value) => {
      if (Number(value) !== 0) {
        form.component = ''
      }
      if (Number(value) !== 1) {
        form.link_url = ''
      }
      if (Number(value) !== 0) {
        form.active_key = ''
      }
    }
  )

  const handleSubmit = async () => {
    if (!formRef.value) {
      return
    }

    await formRef.value.validate()

    submitting.value = true
    try {
      await fetchSaveMenu({
        ...form,
        component: Number(form.type) !== 2 && Number(form.open_type) === 0 ? form.component : '',
        rules: Number(form.type) === 2 ? form.rules : '',
        link_url: Number(form.open_type) === 1 ? form.link_url : '',
        active_key: Number(form.open_type) === 0 ? form.active_key : '',
        hide_children: Number(form.open_type) === 0 ? form.hide_children : 0
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
        nextTick(() => {
          formRef.value?.clearValidate()
        })
      }
    }
  )
</script>

<style scoped>
  .menu-form :deep(.el-input),
  .menu-form :deep(.el-input-number),
  .menu-form :deep(.el-select),
  .menu-form :deep(.el-tree-select) {
    width: 100%;
  }

  .menu-form :deep(.el-radio-group) {
    display: flex;
    flex-wrap: wrap;
    gap: 8px 16px;
  }

  .menu-form :deep(.el-radio) {
    margin-right: 0;
  }

  @media (max-width: 767px) {
    :global(.menu-dialog.el-dialog) {
      max-width: calc(100vw - 24px);
      height: calc(100vh - 16px);
      height: calc(100dvh - 16px);
      max-height: calc(100vh - 16px);
      max-height: calc(100dvh - 16px);
      display: flex;
      flex-direction: column;
      margin-top: 8px !important;
      margin-bottom: 8px !important;
    }

    :global(.menu-dialog .el-dialog__header) {
      flex: 0 0 auto;
      padding: 16px 16px 8px;
      margin-right: 0;
    }

    :global(.menu-dialog .el-dialog__body) {
      flex: 1 1 auto;
      min-height: 0;
      padding: 12px 16px;
      overflow-y: auto;
    }

    :global(.menu-dialog .el-dialog__footer) {
      flex: 0 0 auto;
      padding: 10px 16px 14px;
      border-top: 1px solid var(--el-border-color-lighter);
    }

    .menu-form :deep(.el-form-item) {
      margin-bottom: 16px;
    }

    .menu-form :deep(.el-form-item__content) {
      min-width: 0;
    }
  }
</style>
