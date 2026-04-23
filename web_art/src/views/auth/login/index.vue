<!-- 登录页面 -->
<template>
  <div class="flex w-full h-screen">
    <LoginLeftView />

    <div class="relative flex-1">
      <AuthTopBar />

      <div class="auth-right-wrap">
        <div class="form">
          <h3 class="title">{{ $t('login.title') }}</h3>
          <p class="sub-title">{{ $t('login.subTitle') }}</p>
          <ElForm
            ref="formRef"
            :model="formData"
            :rules="rules"
            :key="formKey"
            @keyup.enter="handleSubmit"
            style="margin-top: 25px"
          >
            <ElFormItem prop="username">
              <ElInput
                class="custom-height"
                :placeholder="$t('login.placeholder.username')"
                v-model.trim="formData.username"
              />
            </ElFormItem>

            <ElFormItem prop="password">
              <ElInput
                class="custom-height"
                :placeholder="$t('login.placeholder.password')"
                v-model.trim="formData.password"
                type="password"
                autocomplete="off"
                show-password
              />
            </ElFormItem>

            <!-- 图片验证码：开启后默认展示，或在自适应模式下由后端触发显示 -->
            <ElFormItem v-if="showCaptcha" prop="captchaCode">
              <div class="captcha-row">
                <ElInput
                  class="custom-height"
                  :placeholder="captchaPlaceholder"
                  v-model.trim="formData.captchaCode"
                  autocomplete="off"
                />

                <button
                  type="button"
                  class="captcha-trigger"
                  :disabled="captchaLoading"
                  @click="handleRefreshCaptcha"
                >
                  <img
                    v-if="captcha.image"
                    :src="captcha.image"
                    class="captcha-image"
                    :alt="captchaPlaceholder"
                  />
                  <span v-else class="captcha-loading">{{ captchaLoadingText }}</span>
                </button>
              </div>
            </ElFormItem>

            <div class="flex-cb mt-2 text-sm">
              <ElCheckbox v-model="formData.rememberPassword">{{
                $t('login.rememberPwd')
              }}</ElCheckbox>
              <RouterLink class="text-theme" :to="{ name: 'ForgetPassword' }">{{
                $t('login.forgetPwd')
              }}</RouterLink>
            </div>

            <div style="margin-top: 30px">
              <ElButton
                class="w-full custom-height"
                type="primary"
                @click="handleSubmit"
                :loading="loading"
                v-ripple
              >
                {{ $t('login.btnText') }}
              </ElButton>
            </div>

            <div class="mt-5 text-sm text-gray-600">
              <span>{{ $t('login.noAccount') }}</span>
              <RouterLink class="text-theme" :to="{ name: 'Register' }">{{
                $t('login.register')
              }}</RouterLink>
            </div>
          </ElForm>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
  import { fetchLogin, fetchLoginCaptcha, fetchLoginCaptchaBootstrap } from '@/api/auth'
  import { useUserStore } from '@/store/modules/user'
  import { HttpError } from '@/utils/http/error'
  import { normalizeLoginRedirect } from '@/utils/navigation/login-redirect'
  import { ElNotification, type FormInstance, type FormRules } from 'element-plus'
  import { useI18n } from 'vue-i18n'

  defineOptions({ name: 'Login' })

  const CAPTCHA_REQUIRED_CODE = 4301
  const CAPTCHA_INVALID_CODE = 4302

  const { t, locale } = useI18n()
  const formKey = ref(0)
  const isZhLocale = computed(() => String(locale.value).toLowerCase().startsWith('zh'))
  const captchaPlaceholder = computed(() =>
    isZhLocale.value ? '请输入验证码' : 'Please enter captcha'
  )
  const captchaLoadingText = computed(() => (isZhLocale.value ? '加载中...' : 'Loading...'))

  // 监听语言切换，重置表单
  watch(locale, () => {
    formKey.value += 1
  })

  const userStore = useUserStore()
  const router = useRouter()
  const route = useRoute()

  const formRef = ref<FormInstance>()
  const loading = ref(false)
  const showCaptcha = ref(false)
  const captchaLoading = ref(false)
  const captchaMeta = reactive<Api.Auth.LoginCaptchaMeta>({
    enabled: false,
    mode: 'always',
    requiredAfterAttempts: 0
  })

  const captcha = reactive<Api.Auth.LoginCaptchaPayload>({
    captchaId: '',
    image: '',
    expireIn: 0
  })

  const formData = reactive({
    username: 'admin',
    password: '123456',
    captchaId: '',
    captchaCode: '',
    rememberPassword: true
  })

  const rules = computed<FormRules>(() => ({
    username: [{ required: true, message: t('login.placeholder.username'), trigger: 'blur' }],
    password: [{ required: true, message: t('login.placeholder.password'), trigger: 'blur' }],
    captchaCode: [
      {
        validator: (_rule: unknown, value: string, callback: (error?: Error) => void) => {
          if (!showCaptcha.value || String(value || '').trim()) {
            callback()
            return
          }

          callback(new Error(captchaPlaceholder.value))
        },
        trigger: 'blur'
      }
    ]
  }))

  onMounted(() => {
    redirectIfLoggedIn()
    void loadCaptchaBootstrap()
  })

  const getAuthenticatedRedirect = () => {
    return normalizeLoginRedirect(route.query.redirect)
  }

  const redirectIfLoggedIn = () => {
    if (!userStore.isLogin || loading.value) {
      return
    }

    router.replace(getAuthenticatedRedirect())
  }

  watch(
    () => userStore.isLogin,
    (value) => {
      if (!value) {
        return
      }

      redirectIfLoggedIn()
    },
    { immediate: true }
  )

  const resetCaptchaState = () => {
    showCaptcha.value = false
    captcha.captchaId = ''
    captcha.image = ''
    captcha.expireIn = 0
    formData.captchaId = ''
    formData.captchaCode = ''
  }

  const applyCaptchaMeta = (payload?: Partial<Api.Auth.LoginCaptchaMeta> | null) => {
    captchaMeta.enabled = Boolean(payload?.enabled)
    captchaMeta.mode = payload?.mode === 'adaptive' ? 'adaptive' : 'always'
    captchaMeta.requiredAfterAttempts = Number(payload?.requiredAfterAttempts || 0)
  }

  const applyCaptcha = (payload?: Partial<Api.Auth.LoginCaptchaPayload> | null) => {
    if (!payload?.captchaId || !payload?.image) {
      return false
    }

    showCaptcha.value = true
    captcha.captchaId = payload.captchaId
    captcha.image = payload.image
    captcha.expireIn = Number(payload.expireIn || 0)
    formData.captchaId = payload.captchaId
    formData.captchaCode = ''
    return true
  }

  const extractCaptchaPayload = (data: unknown) => {
    if (!data || typeof data !== 'object') {
      return null
    }

    const payload = (data as Record<string, any>).captcha
    if (!payload || typeof payload !== 'object') {
      return null
    }

    return payload as Partial<Api.Auth.LoginCaptchaPayload>
  }

  const extractCaptchaMeta = (data: unknown) => {
    if (!data || typeof data !== 'object') {
      return null
    }

    const payload = (data as Record<string, any>).captchaMeta
    if (!payload || typeof payload !== 'object') {
      return null
    }

    return payload as Partial<Api.Auth.LoginCaptchaMeta>
  }

  const focusCaptchaField = async () => {
    await nextTick()
    const validation = formRef.value?.validateField('captchaCode')
    if (validation) {
      await validation.catch(() => undefined)
    }
  }

  // 登录页初始化改成一次请求，避免先取配置再取图片造成输入框出现更慢。
  const loadCaptchaBootstrap = async () => {
    try {
      const payload = await fetchLoginCaptchaBootstrap({ showErrorMessage: false })
      applyCaptchaMeta(payload.meta)

      if (payload.captcha) {
        applyCaptcha(payload.captcha)
      } else if (!captchaMeta.enabled || captchaMeta.mode === 'adaptive') {
        resetCaptchaState()
      }
    } catch {
      applyCaptchaMeta(null)
    }
  }

  const refreshCaptcha = async (showErrorMessage = false) => {
    if (!captchaMeta.enabled) {
      resetCaptchaState()
      return
    }

    captchaLoading.value = true

    try {
      const payload = await fetchLoginCaptcha({ showErrorMessage })
      applyCaptcha(payload)
      showCaptcha.value = true
    } finally {
      captchaLoading.value = false
    }
  }

  const handleRefreshCaptcha = () => refreshCaptcha()

  const ensureCaptcha = async (data?: unknown) => {
    applyCaptchaMeta(extractCaptchaMeta(data))

    if (applyCaptcha(extractCaptchaPayload(data))) {
      return
    }

    await refreshCaptcha(false)
  }

  const handleLoginError = async (error: HttpError) => {
    if ([CAPTCHA_REQUIRED_CODE, CAPTCHA_INVALID_CODE].includes(error.code)) {
      await ensureCaptcha(error.data)
      await focusCaptchaField()
      return
    }

    // 验证码模式开启时，普通失败也刷新验证码，避免验证码被消费后页面还是旧图。
    if (showCaptcha.value) {
      formData.captchaCode = ''
      await refreshCaptcha(false).catch(() => undefined)
    }
  }

  // 登录
  const handleSubmit = async () => {
    if (!formRef.value) return

    try {
      // 表单验证
      const valid = await formRef.value.validate()
      if (!valid) return

      loading.value = true

      // 登录请求
      const { username, password, captchaId, captchaCode } = formData

      const { token, refreshToken } = await fetchLogin({
        userName: username,
        password,
        captchaId,
        captchaCode
      })

      // 验证token
      if (!token) {
        throw new Error('Login failed - no token received')
      }

      // 存储 token 和登录状态
      userStore.setToken(token, refreshToken)
      userStore.setLoginStatus(true)

      // 登录成功处理
      showLoginSuccessNotice()

      // 登录页会在路由跳转后卸载，不提前清空验证码，避免加载中表单高度跳动。
      await router.push(getAuthenticatedRedirect())
    } catch (error) {
      // 处理 HttpError
      if (error instanceof HttpError) {
        await handleLoginError(error)
      } else {
        // 处理非 HttpError
        console.error('[Login] Unexpected error:', error)
      }
    } finally {
      loading.value = false
    }
  }

  // 登录成功提示
  const getLoginSuccessDisplayName = () => {
    const { realname, userName, username } = userStore.info
    return realname || userName || username || formData.username
  }

  const showLoginSuccessNotice = () => {
    setTimeout(() => {
      ElNotification({
        title: t('login.success.title'),
        type: 'success',
        duration: 2500,
        zIndex: 10000,
        message: `${t('login.success.message')}, ${getLoginSuccessDisplayName()}!`
      })
    }, 1000)
  }
</script>

<style scoped>
  @import './style.css';
</style>
