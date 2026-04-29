<template>
  <ElDialog
    v-model="visible"
    :title="props.type === 'add' ? '新增用户' : '编辑用户'"
    class="system-user-dialog system-form-dialog"
    width="min(700px, calc(100vw - 32px))"
    align-center
    @closed="handleClosed"
  >
    <ElForm
      ref="formRef"
      class="system-form-dialog__form"
      :model="form"
      :rules="rules"
      label-width="90px"
    >
      <ElRow :gutter="16">
        <ElCol :xs="24" :sm="24" :md="12">
          <ElFormItem label="用户名" prop="username">
            <ElInput
              v-model="form.username"
              :disabled="props.type === 'edit'"
              placeholder="请输入用户名"
            />
          </ElFormItem>
        </ElCol>

        <ElCol :xs="24" :sm="24" :md="12">
          <ElFormItem label="姓名" prop="realname">
            <ElInput v-model="form.realname" placeholder="请输入姓名" />
          </ElFormItem>
        </ElCol>

        <ElCol :xs="24" :sm="24" :md="12">
          <ElFormItem label="手机号" prop="phone">
            <ElInput v-model="form.phone" placeholder="请输入手机号" />
          </ElFormItem>
        </ElCol>

        <ElCol :xs="24" :sm="24" :md="12">
          <ElFormItem label="邮箱" prop="email">
            <ElInput v-model="form.email" placeholder="请输入邮箱" />
          </ElFormItem>
        </ElCol>

        <ElCol :xs="24" :sm="24" :md="12">
          <ElFormItem label="角色" prop="roles">
            <ElSelect v-model="form.roles" multiple placeholder="请选择角色" style="width: 100%">
              <ElOption
                v-for="item in roleOptions"
                :key="item.id"
                :label="item.name"
                :value="item.id"
              />
            </ElSelect>
          </ElFormItem>
        </ElCol>

        <ElCol :xs="24" :sm="24" :md="12">
          <ElFormItem label="部门" prop="dept_id">
            <ElTreeSelect
              v-model="form.dept_id"
              :data="departmentOptions"
              clearable
              filterable
              node-key="id"
              style="width: 100%"
              placeholder="请选择部门"
              :props="treeProps"
            />
          </ElFormItem>
        </ElCol>

        <ElCol :span="24">
          <ElFormItem label="头像" prop="avatar">
            <div class="w-full flex flex-col gap-3">
              <BackendImageUpload v-model="form.avatar" />
              <ElInput
                v-model="form.avatar"
                placeholder="上传后会自动回填，也可以直接填写头像地址"
              />
            </div>
          </ElFormItem>
        </ElCol>
      </ElRow>
    </ElForm>

    <div v-if="props.type === 'add'" class="text-sm text-[var(--el-color-warning)]">
      默认初始密码为 `123456`
    </div>

    <template #footer>
      <ElButton @click="visible = false">取消</ElButton>
      <ElButton type="primary" :loading="submitting" @click="handleSubmit">保存</ElButton>
    </template>
  </ElDialog>
</template>

<script setup lang="ts">
  import type { FormInstance, FormRules } from 'element-plus'
  import { fetchGetUserDetail, fetchSaveUser } from '@/api/system-manage'

  type DialogType = 'add' | 'edit'

  interface Props {
    visible: boolean
    type: DialogType
    userData?: Partial<Api.SystemManage.UserListItem>
    roleOptions?: Api.SystemManage.RoleOption[]
    departmentOptions?: Api.SystemManage.DepartmentOption[]
  }

  interface Emits {
    (e: 'update:visible', value: boolean): void
    (e: 'success', type: DialogType): void
  }

  const props = withDefaults(defineProps<Props>(), {
    visible: false,
    type: 'add',
    userData: () => ({}),
    roleOptions: () => [],
    departmentOptions: () => []
  })

  const emit = defineEmits<Emits>()

  const formRef = ref<FormInstance>()
  const loading = ref(false)
  const submitting = ref(false)

  const visible = computed({
    get: () => props.visible,
    set: (value) => emit('update:visible', value)
  })

  const form = reactive<
    Api.SystemManage.UserPayload & {
      username: string
    }
  >({
    id: undefined,
    username: '',
    realname: '',
    phone: '',
    email: '',
    dept_id: '',
    roles: [],
    avatar: ''
  })

  const treeProps = {
    label: 'name',
    value: 'id',
    children: 'children'
  }

  const rules = reactive<FormRules>({
    username: [
      {
        validator: (_rule, value, callback) => {
          if (props.type === 'add' && !value) {
            callback(new Error('请输入用户名'))
            return
          }
          callback()
        },
        trigger: 'blur'
      }
    ],
    realname: [{ required: true, message: '请输入姓名', trigger: 'blur' }],
    roles: [{ required: true, message: '请选择角色', trigger: 'change' }],
    dept_id: [{ required: true, message: '请选择部门', trigger: 'change' }],
    email: [{ type: 'email', message: '邮箱格式不正确', trigger: 'blur' }]
  })

  const resetForm = () => {
    Object.assign(form, {
      id: undefined,
      username: '',
      realname: '',
      phone: '',
      email: '',
      dept_id: '',
      roles: [],
      avatar: ''
    })
  }

  const fillForm = async () => {
    resetForm()

    if (props.type !== 'edit' || !props.userData?.id) {
      return
    }

    loading.value = true
    try {
      const detail = await fetchGetUserDetail(props.userData.id)
      Object.assign(form, {
        id: detail.id,
        username: detail.username || '',
        realname: detail.realname || '',
        phone: detail.phone || '',
        email: detail.email || '',
        dept_id: detail.dept_id || '',
        roles: Array.isArray(detail.roles) ? detail.roles : [],
        avatar: detail.avatar || ''
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
      await fetchSaveUser({
        id: form.id,
        username: form.username,
        realname: form.realname,
        phone: form.phone,
        email: form.email,
        dept_id: form.dept_id,
        roles: form.roles,
        avatar: form.avatar
      })
      visible.value = false
      emit('success', props.type)
    } finally {
      submitting.value = false
    }
  }

  const handleClosed = () => {
    formRef.value?.clearValidate()
    resetForm()
  }

  watch(
    () => props.visible,
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

<style lang="scss">
  .system-user-dialog.el-dialog {
    display: flex;
    flex-direction: column;
    max-height: calc(100dvh - 32px);
  }

  .system-user-dialog.el-dialog .el-dialog__body {
    flex: 1;
    min-height: 0;
    padding: 20px 24px !important;
    overflow-x: hidden;
    overflow-y: auto;
  }

  .system-user-dialog.el-dialog .el-dialog__footer {
    flex-shrink: 0;
  }

  .system-user-dialog {
    .system-form-dialog__form {
      min-width: 0;
    }
  }

  @media screen and (max-width: 768px) {
    .system-user-dialog.el-dialog {
      width: calc(100vw - 24px) !important;
      max-height: calc(100dvh - 24px);
    }

    .system-user-dialog.el-dialog .el-dialog__body {
      padding: 16px !important;
    }

    .system-user-dialog {
      .el-form-item {
        display: block;
      }

      .el-form-item__label {
        justify-content: flex-start;
        width: 100% !important;
        height: auto !important;
        margin-bottom: 8px;
        line-height: 20px !important;
        text-align: left;
      }

      .el-form-item__content {
        width: 100%;
        margin-left: 0 !important;
      }

      .backend-image-upload,
      .backend-image-upload__trigger,
      .backend-image-upload__footer {
        width: 100%;
      }

      .backend-image-upload__footer {
        align-items: flex-start;
        flex-direction: column;
        gap: 6px;
      }
    }
  }
</style>
