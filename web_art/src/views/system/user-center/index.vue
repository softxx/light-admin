<template>
  <div class="user-center-page">
    <ElRow :gutter="20">
      <ElCol :xl="7" :lg="8" :md="24">
        <ElCard shadow="never" class="mb-5">
          <div class="flex flex-col items-center text-center">
            <BackendImageUpload v-model="profileForm.avatar" :disabled="!isProfileEditing" />
            <h2 class="mt-5 text-xl font-semibold text-g-900">
              {{ userInfo.userName || userInfo.username || '未命名用户' }}
            </h2>
            <p class="mt-2 text-sm text-g-500">{{ userInfo.username || '--' }}</p>

          </div>

          <ElDescriptions :column="1" border class="mt-6">
            <ElDescriptionsItem label="邮箱">
              {{ userInfo.email || '--' }}
            </ElDescriptionsItem>
            <ElDescriptionsItem label="手机号">
              {{ userInfo.phone || '--' }}
            </ElDescriptionsItem>
          </ElDescriptions>
        </ElCard>

        <ElCard shadow="never">
          <template #header>
            <span class="font-medium">使用说明</span>
          </template>

          <div class="text-sm leading-7 text-g-600">
            <p>这里维护的是当前登录账号的个人资料。</p>
            <p>头像上传后会自动回填到表单，可以继续手动调整地址。</p>
            <p>密码修改成功后会自动退出，请使用新密码重新登录。</p>
          </div>
        </ElCard>
      </ElCol>

      <ElCol :xl="17" :lg="16" :md="24">
        <ElCard shadow="never" class="mb-5">
          <template #header>
            <div class="flex items-center justify-between gap-3">
              <div>
                <span class="font-medium">基本资料</span>
                <div class="mt-1 text-xs text-g-500">保存后会立即同步到当前会话</div>
              </div>
              <div class="flex gap-2">
                <ElButton
                  v-if="!isProfileEditing"
                  type="primary"
                  :disabled="profileSubmitting"
                  @click="handleStartProfileEdit"
                >
                  编辑资料
                </ElButton>
                <template v-else>
                  <ElButton :disabled="profileSubmitting" @click="resetProfileForm">重置</ElButton>
                  <ElButton type="primary" :loading="profileSubmitting" @click="handleSaveProfile">
                    保存资料
                  </ElButton>
                  <ElButton :disabled="profileSubmitting" @click="handleCancelProfileEdit">
                    取消编辑
                  </ElButton>
                </template>
              </div>
            </div>
          </template>

          <ElForm
            ref="profileFormRef"
            :model="profileForm"
            :rules="profileRules"
            label-width="92px"
          >
            <ElRow :gutter="16">
              <ElCol :md="12" :sm="24">
                <ElFormItem label="姓名" prop="realname">
                  <ElInput
                    v-model="profileForm.realname"
                    :disabled="!isProfileEditing"
                    placeholder="请输入姓名"
                  />
                </ElFormItem>
              </ElCol>

              <ElCol :md="12" :sm="24">
                <ElFormItem label="手机号" prop="phone">
                  <ElInput
                    v-model="profileForm.phone"
                    :disabled="!isProfileEditing"
                    placeholder="请输入手机号"
                  />
                </ElFormItem>
              </ElCol>

              <ElCol :md="12" :sm="24">
                <ElFormItem label="邮箱" prop="email">
                  <ElInput
                    v-model="profileForm.email"
                    :disabled="!isProfileEditing"
                    placeholder="请输入邮箱"
                  />
                </ElFormItem>
              </ElCol>

              <ElCol :md="12" :sm="24">
                <ElFormItem label="账号">
                  <ElInput :model-value="userInfo.username || ''" disabled />
                </ElFormItem>
              </ElCol>

              <ElCol :span="24">
                <ElFormItem label="头像地址" prop="avatar">
                  <ElInput
                    v-model="profileForm.avatar"
                    :disabled="!isProfileEditing"
                    placeholder="上传后会自动回填，也可以直接填写头像地址"
                  />
                </ElFormItem>
              </ElCol>
            </ElRow>
          </ElForm>
        </ElCard>

        <ElCard shadow="never">
          <template #header>
            <div class="flex items-center justify-between gap-3">
              <span class="font-medium">修改密码</span>
              <div class="flex gap-2">
                <ElButton
                  v-if="!isPasswordEditing"
                  type="primary"
                  :disabled="passwordSubmitting"
                  @click="handleStartPasswordEdit"
                >
                  编辑密码
                </ElButton>
                <template v-else>
                  <ElButton :disabled="passwordSubmitting" @click="resetPasswordForm">
                    重置
                  </ElButton>
                  <ElButton
                    type="primary"
                    :loading="passwordSubmitting"
                    @click="handleChangePassword"
                  >
                    更新密码
                  </ElButton>
                  <ElButton :disabled="passwordSubmitting" @click="handleCancelPasswordEdit">
                    取消编辑
                  </ElButton>
                </template>
              </div>
            </div>
          </template>

          <ElAlert
            title="密码更新成功后会自动退出当前账号"
            type="warning"
            show-icon
            :closable="false"
            class="mb-5"
          />

          <ElForm
            ref="passwordFormRef"
            :model="passwordForm"
            :rules="passwordRules"
            label-width="92px"
          >
            <ElFormItem label="当前密码" prop="password_old">
              <ElInput
                v-model="passwordForm.password_old"
                type="password"
                :disabled="!isPasswordEditing"
                show-password
                placeholder="请输入当前密码"
              />
            </ElFormItem>

            <ElFormItem label="新密码" prop="password">
              <ElInput
                v-model="passwordForm.password"
                type="password"
                :disabled="!isPasswordEditing"
                show-password
                placeholder="请输入新密码"
              />
            </ElFormItem>

            <ElFormItem label="确认密码" prop="password_confirm">
              <ElInput
                v-model="passwordForm.password_confirm"
                type="password"
                :disabled="!isPasswordEditing"
                show-password
                placeholder="请再次输入新密码"
              />
            </ElFormItem>
          </ElForm>
        </ElCard>
      </ElCol>
    </ElRow>
  </div>
</template>

<script setup lang="ts">
  import type { FormInstance, FormRules } from 'element-plus'
  import { fetchGetUserInfo, fetchLogout } from '@/api/auth'
  import { fetchChangePassword, fetchUpdateUserInfo } from '@/api/system-manage'
  import { useUserStore } from '@/store/modules/user'

  defineOptions({ name: 'UserCenter' })

  const userStore = useUserStore()
  // User profile no longer displays department or role data.
  const userInfo = computed(() => userStore.getUserInfo)

  const profileFormRef = ref<FormInstance>()
  const passwordFormRef = ref<FormInstance>()
  const profileSubmitting = ref(false)
  const passwordSubmitting = ref(false)
  const isProfileEditing = ref(false)
  const isPasswordEditing = ref(false)

  const profileForm = reactive({
    realname: '',
    phone: '',
    email: '',
    avatar: ''
  })

  const passwordForm = reactive({
    password_old: '',
    password: '',
    password_confirm: ''
  })

  const syncProfileForm = (info: Partial<Api.Auth.UserInfo> = userInfo.value) => {
    Object.assign(profileForm, {
      realname: info.realname || '',
      phone: info.phone || '',
      email: info.email || '',
      avatar: info.avatar || ''
    })
  }

  const resetProfileForm = () => {
    syncProfileForm()
    profileFormRef.value?.clearValidate()
  }

  const resetPasswordForm = () => {
    Object.assign(passwordForm, {
      password_old: '',
      password: '',
      password_confirm: ''
    })
    passwordFormRef.value?.clearValidate()
  }

  const handleStartProfileEdit = () => {
    isProfileEditing.value = true
  }

  const handleCancelProfileEdit = () => {
    resetProfileForm()
    isProfileEditing.value = false
  }

  const handleStartPasswordEdit = () => {
    isPasswordEditing.value = true
  }

  const handleCancelPasswordEdit = () => {
    resetPasswordForm()
    isPasswordEditing.value = false
  }

  const reloadUserInfo = async () => {
    const info = await fetchGetUserInfo()
    userStore.setUserInfo(info)
    syncProfileForm(info)
  }

  const validatePhone = (_rule: unknown, value: string, callback: (error?: Error) => void) => {
    if (!value) {
      callback()
      return
    }

    if (!/^1[3-9]\d{9}$/.test(value)) {
      callback(new Error('手机号格式不正确'))
      return
    }

    callback()
  }

  const validatePassword = (_rule: unknown, value: string, callback: (error?: Error) => void) => {
    if (!value) {
      callback(new Error('请输入新密码'))
      return
    }

    if (!/^(?![^a-zA-Z]+$)(?!\D+$).{6,}$/.test(value)) {
      callback(new Error('密码需包含字母和数字，且不少于 6 位'))
      return
    }

    callback()
  }

  const validateConfirmPassword = (
    _rule: unknown,
    value: string,
    callback: (error?: Error) => void
  ) => {
    if (!value) {
      callback(new Error('请再次输入新密码'))
      return
    }

    if (value !== passwordForm.password) {
      callback(new Error('两次输入的密码不一致'))
      return
    }

    callback()
  }

  const profileRules = reactive<FormRules>({
    realname: [{ required: true, message: '请输入姓名', trigger: 'blur' }],
    phone: [{ validator: validatePhone, trigger: 'blur' }],
    email: [{ type: 'email', message: '邮箱格式不正确', trigger: 'blur' }]
  })

  const passwordRules = reactive<FormRules>({
    password_old: [{ required: true, message: '请输入当前密码', trigger: 'blur' }],
    password: [{ validator: validatePassword, trigger: 'blur' }],
    password_confirm: [{ validator: validateConfirmPassword, trigger: 'blur' }]
  })

  const handleSaveProfile = async () => {
    if (!profileFormRef.value) {
      return
    }

    await profileFormRef.value.validate()

    profileSubmitting.value = true
    try {
      await fetchUpdateUserInfo({
        realname: profileForm.realname,
        phone: profileForm.phone,
        email: profileForm.email,
        avatar: profileForm.avatar
      })
      await reloadUserInfo()
      isProfileEditing.value = false
    } finally {
      profileSubmitting.value = false
    }
  }

  const handleChangePassword = async () => {
    if (!passwordFormRef.value) {
      return
    }

    await passwordFormRef.value.validate()

    passwordSubmitting.value = true
    try {
      await fetchChangePassword({ ...passwordForm })

      if (userStore.refreshToken) {
        await fetchLogout(userStore.refreshToken).catch(() => undefined)
      }

      userStore.logOut()
    } finally {
      passwordSubmitting.value = false
    }
  }

  onMounted(() => {
    syncProfileForm()
  })
</script>

<style scoped lang="scss">
  .user-center-page {
    :deep(.el-card__header) {
      padding: 18px 20px;
    }

    :deep(.el-card__body) {
      padding: 20px;
    }
  }
</style>
