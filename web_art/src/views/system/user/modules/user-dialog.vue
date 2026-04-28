<template>
  <ElDialog
    v-model="visible"
    :title="props.type === 'add' ? '新增用户' : '编辑用户'"
    width="700px"
    align-center
    @closed="handleClosed"
  >
    <ElForm ref="formRef" :model="form" :rules="rules" label-width="90px">
      <ElRow :gutter="16">
        <ElCol :span="12">
          <ElFormItem label="用户名" prop="username">
            <ElInput
              v-model="form.username"
              :disabled="props.type === 'edit'"
              placeholder="请输入用户名"
            />
          </ElFormItem>
        </ElCol>

        <ElCol :span="12">
          <ElFormItem label="姓名" prop="realname">
            <ElInput v-model="form.realname" placeholder="请输入姓名" />
          </ElFormItem>
        </ElCol>

        <ElCol :span="12">
          <ElFormItem label="手机号" prop="phone">
            <ElInput v-model="form.phone" placeholder="请输入手机号" />
          </ElFormItem>
        </ElCol>

        <ElCol :span="12">
          <ElFormItem label="邮箱" prop="email">
            <ElInput v-model="form.email" placeholder="请输入邮箱" />
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
  }

  interface Emits {
    (e: 'update:visible', value: boolean): void
    (e: 'success', type: DialogType): void
  }

  const props = withDefaults(defineProps<Props>(), {
    visible: false,
    type: 'add',
    userData: () => ({})
  })

  const emit = defineEmits<Emits>()

  const formRef = ref<FormInstance>()
  const loading = ref(false)
  const submitting = ref(false)

  const visible = computed({
    get: () => props.visible,
    set: (value) => emit('update:visible', value)
  })

  // 部门和角色字段已移除，新增/编辑用户只维护账号基础信息。
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
    avatar: ''
  })

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
    email: [{ type: 'email', message: '邮箱格式不正确', trigger: 'blur' }]
  })

  const resetForm = () => {
    Object.assign(form, {
      id: undefined,
      username: '',
      realname: '',
      phone: '',
      email: '',
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
