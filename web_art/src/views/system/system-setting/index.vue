<template>
  <div class="system-setting-page art-full-height">
    <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_360px]">
      <ElCard class="system-setting-page__card">
        <template #header>
          <div class="flex-cb gap-3">
            <div>
              <div class="text-lg font-semibold">系统设置</div>
              <div class="mt-1 text-sm text-[var(--art-gray-500)]">
                统一维护系统名称、品牌图标和首页介绍内容。
              </div>
            </div>

            <div class="flex gap-2">
              <ElButton
                v-if="canUpdateSetting && !isEditing"
                type="primary"
                :disabled="loading"
                @click="handleStartEdit"
                v-ripple
              >
                编辑设置
              </ElButton>
              <template v-else-if="isEditing">
                <ElButton :disabled="loading || saving" @click="handleReset">重置</ElButton>
                <ElButton
                  v-if="canUpdateSetting"
                  type="primary"
                  :loading="saving"
                  @click="handleSave"
                  v-ripple
                >
                  保存设置
                </ElButton>
              </template>
              <ElButton
                v-if="canUpdateSetting && isEditing"
                type="primary"
                plain
                :disabled="saving"
                @click="handleCancelEdit"
              >
                取消编辑
              </ElButton>
            </div>
          </div>
        </template>

        <ElForm ref="formRef" :model="form" :rules="rules" label-width="108px" class="max-w-3xl">
          <ElFormItem label="系统名称" prop="system_name">
            <ElInput
              v-model.trim="form.system_name"
              :disabled="!isEditing"
              maxlength="100"
              show-word-limit
              placeholder="请输入系统名称"
            />
          </ElFormItem>

          <ElFormItem label="系统 Logo" prop="logo">
            <div class="system-setting-page__upload">
              <BackendImageUpload
                v-model="form.logo"
                :disabled="!isEditing"
                :round="false"
                placeholder="上传 Logo"
                tip="用于登录页和菜单顶部，不上传时沿用当前默认 Logo。"
              />
              <div class="system-setting-page__tip">
                建议上传清晰的方形图片，保存后登录页和后台菜单顶部会同步更新。
              </div>
            </div>
          </ElFormItem>

          <ElFormItem label="Favicon" prop="favicon">
            <div class="system-setting-page__upload">
              <BackendImageUpload
                v-model="form.favicon"
                :disabled="!isEditing"
                :round="false"
                placeholder="上传图标"
                tip="用于浏览器标签页图标，不上传时沿用当前默认 favicon。"
              />
              <div class="system-setting-page__tip">
                保存后浏览器标签页图标会即时更新。
              </div>
            </div>
          </ElFormItem>

          <ElFormItem label="首页开关" prop="homepage_enabled">
            <div class="w-full">
              <div class="flex items-center gap-3">
                <ElSwitch
                  v-model="form.homepage_enabled"
                  :disabled="!isEditing"
                  :active-value="1"
                  :inactive-value="0"
                />
                <span class="text-sm text-[var(--art-gray-600)]">
                  {{ previewHomepageEnabled ? '开启首页' : '关闭首页' }}
                </span>
              </div>
              <div class="system-setting-page__tip">
                开启时访问网站会先显示首页，关闭后访问网站会直接进入登录页；已登录用户会直接进入工作台。
              </div>
            </div>
          </ElFormItem>

          <ElFormItem label="首页标题" prop="homepage_title">
            <ElInput
              v-model.trim="form.homepage_title"
              :disabled="!isEditing"
              maxlength="100"
              show-word-limit
              placeholder="请输入首页标题"
            />
          </ElFormItem>

          <ElFormItem label="首页简介" prop="homepage_intro">
            <div class="w-full">
              <ElInput
                v-model.trim="form.homepage_intro"
                :disabled="!isEditing"
                type="textarea"
                :rows="5"
                maxlength="1000"
                show-word-limit
                resize="vertical"
                placeholder="请输入首页简介"
              />
              <div class="system-setting-page__tip">
                这里先放项目介绍即可，等你后面想清楚首页方向后再继续补充内容。
              </div>
            </div>
          </ElFormItem>
        </ElForm>
      </ElCard>

      <div class="flex flex-col gap-4">
        <ElCard>
          <template #header>
            <div class="font-semibold">实时预览</div>
          </template>

          <div class="system-setting-preview" :class="{ 'is-dark': isDarkTheme }">
            <div class="system-setting-preview__block">
              <div class="system-setting-preview__label">登录页</div>
              <div class="system-setting-preview__hero">
                <img :src="previewLogo" alt="system-logo" class="system-setting-preview__logo" />
                <div>
                  <div class="text-base font-semibold">{{ previewSystemName }}</div>
                  <div class="mt-1 text-xs text-[var(--art-gray-500)]">登录页左上角展示效果</div>
                </div>
              </div>
            </div>

            <div class="system-setting-preview__block">
              <div class="system-setting-preview__label">菜单顶部</div>
              <div class="system-setting-preview__menu">
                <img :src="previewLogo" alt="menu-logo" class="system-setting-preview__menu-logo" />
                <span class="truncate">{{ previewSystemName }}</span>
              </div>
            </div>

            <div class="system-setting-preview__block">
              <div class="system-setting-preview__label">浏览器标签</div>
              <div class="system-setting-preview__favicon">
                <img
                  :src="previewFavicon"
                  alt="favicon"
                  class="system-setting-preview__favicon-icon"
                />
                <span class="truncate">系统设置 - {{ previewSystemName }}</span>
              </div>
            </div>

            <div class="system-setting-preview__block">
              <div class="system-setting-preview__label">首页展示</div>
              <div class="system-setting-preview__status" :class="{ 'is-disabled': !previewHomepageEnabled }">
                {{ previewHomepageEnabled ? '首页已开启' : '首页已关闭，访问网站会直接跳转登录页' }}
              </div>
              <div v-if="previewHomepageEnabled" class="system-setting-preview__home">
                <div class="system-setting-preview__home-title">{{ previewHomepageTitle }}</div>
                <div class="system-setting-preview__home-desc">{{ previewHomepageIntro }}</div>
                <div class="system-setting-preview__button">进入后台管理</div>
              </div>
              <div v-else class="system-setting-preview__disabled">
                关闭后根路径不再展示首页内容，未登录用户会直接进入登录页，已登录用户会直接进入工作台。
              </div>
            </div>
          </div>
        </ElCard>

        <ElCard>
          <template #header>
            <div class="font-semibold">说明</div>
          </template>

          <div class="text-sm leading-7 text-[var(--art-gray-600)]">
            <p>1. 系统名称会同步影响登录页、菜单顶部和页面标题。</p>
            <p>2. 首页开关关闭后，访问网站会直接跳到登录页，已登录用户会直接进入工作台。</p>
            <p>3. 首页标题和简介先作为简洁介绍文案，后续可以继续扩展，不需要再改源码。</p>
            <p>4. Logo 和 Favicon 保存后会立即同步到前端品牌展示。</p>
          </div>
        </ElCard>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
  import defaultLogo from '@imgs/common/logo.webp'
  import defaultFavicon from '@imgs/favicon.ico'
  import { fetchGetSystemSetting, fetchUpdateSystemSetting } from '@/api/system-manage'
  import { useAuth } from '@/hooks'
  import { useSettingStore } from '@/store/modules/setting'
  import { useSystemConfigStore } from '@/store/modules/system-config'
  import { setPageTitle } from '@/utils/router'
  import type { FormInstance, FormRules } from 'element-plus'

  defineOptions({ name: 'SystemSetting' })

  const { hasAuth } = useAuth()
  const canUpdateSetting = hasAuth('system:systemsetting:update')
  const settingStore = useSettingStore()
  const systemConfigStore = useSystemConfigStore()
  const router = useRouter()

  const defaultSystemName = 'Art Design Pro'
  const defaultHomepageEnabled = 1
  const defaultHomepageTitle = '项目管理平台'
  const defaultHomepageIntro =
    '这里先作为项目的简洁首页，展示项目介绍与后台入口。后续可以在系统设置中继续调整文案，不需要每次修改源码。'

  const formRef = ref<FormInstance>()
  const loading = ref(false)
  const saving = ref(false)
  const isEditing = ref(false)

  const initialForm = ref<Api.SystemManage.SystemSettingPayload>({
    system_name: defaultSystemName,
    logo: '',
    favicon: '',
    homepage_enabled: defaultHomepageEnabled,
    homepage_title: defaultHomepageTitle,
    homepage_intro: defaultHomepageIntro
  })

  const form = reactive<Api.SystemManage.SystemSettingPayload>({
    system_name: defaultSystemName,
    logo: '',
    favicon: '',
    homepage_enabled: defaultHomepageEnabled,
    homepage_title: defaultHomepageTitle,
    homepage_intro: defaultHomepageIntro
  })

  const rules = reactive<FormRules<Api.SystemManage.SystemSettingPayload>>({
    system_name: [{ required: true, message: '请输入系统名称', trigger: 'blur' }],
    homepage_title: [{ max: 100, message: '首页标题不能超过100个字符', trigger: 'blur' }],
    homepage_intro: [{ max: 1000, message: '首页简介不能超过1000个字符', trigger: 'blur' }]
  })

  const previewSystemName = computed(() => form.system_name?.trim() || defaultSystemName)
  const previewLogo = computed(() => form.logo || defaultLogo)
  const previewFavicon = computed(() => form.favicon || defaultFavicon)
  const previewHomepageEnabled = computed(() => Number(form.homepage_enabled ?? defaultHomepageEnabled) !== 0)
  const previewHomepageTitle = computed(() => form.homepage_title?.trim() || defaultHomepageTitle)
  const previewHomepageIntro = computed(() => form.homepage_intro?.trim() || defaultHomepageIntro)
  const isDarkTheme = computed(() => settingStore.isDark)

  const assignForm = (payload: Api.SystemManage.SystemSettingPayload) => {
    form.system_name = payload.system_name || defaultSystemName
    form.logo = payload.logo || ''
    form.favicon = payload.favicon || ''
    form.homepage_enabled = Number(payload.homepage_enabled ?? defaultHomepageEnabled) === 0 ? 0 : 1
    form.homepage_title = payload.homepage_title || defaultHomepageTitle
    form.homepage_intro = payload.homepage_intro || defaultHomepageIntro
  }

  const loadSetting = async () => {
    loading.value = true

    try {
      const data = await fetchGetSystemSetting()
      initialForm.value = {
        system_name: data.system_name || defaultSystemName,
        logo: data.logo || '',
        favicon: data.favicon || '',
        homepage_enabled: Number(data.homepage_enabled ?? defaultHomepageEnabled) === 0 ? 0 : 1,
        homepage_title: data.homepage_title || defaultHomepageTitle,
        homepage_intro: data.homepage_intro || defaultHomepageIntro
      }
      assignForm(initialForm.value)
      systemConfigStore.setSystemSetting(initialForm.value)
    } finally {
      loading.value = false
    }
  }

  const handleStartEdit = () => {
    isEditing.value = true
  }

  const handleReset = () => {
    assignForm(initialForm.value)
    formRef.value?.clearValidate?.()
  }

  const handleCancelEdit = () => {
    handleReset()
    isEditing.value = false
  }

  const handleSave = async () => {
    if (!formRef.value) return

    await formRef.value.validate()
    saving.value = true

    try {
      const data = await fetchUpdateSystemSetting({
        system_name: previewSystemName.value,
        logo: form.logo || '',
        favicon: form.favicon || '',
        homepage_enabled: previewHomepageEnabled.value ? 1 : 0,
        homepage_title: previewHomepageTitle.value,
        homepage_intro: previewHomepageIntro.value
      })

      initialForm.value = {
        system_name: data.system_name || defaultSystemName,
        logo: data.logo || '',
        favicon: data.favicon || '',
        homepage_enabled: Number(data.homepage_enabled ?? defaultHomepageEnabled) === 0 ? 0 : 1,
        homepage_title: data.homepage_title || defaultHomepageTitle,
        homepage_intro: data.homepage_intro || defaultHomepageIntro
      }

      assignForm(initialForm.value)
      systemConfigStore.setSystemSetting(initialForm.value)
      setPageTitle(router.currentRoute.value)
      isEditing.value = false
    } finally {
      saving.value = false
    }
  }

  onMounted(() => {
    loadSetting()
  })
</script>

<style scoped lang="scss">
  .system-setting-page__card {
    min-height: 100%;
  }

  .system-setting-page__upload {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 18px;
  }

  .system-setting-page__tip {
    margin-top: 8px;
    font-size: 13px;
    line-height: 1.7;
    color: var(--art-gray-500);
  }

  .system-setting-preview {
    --preview-card-border: var(--el-border-color-lighter);
    --preview-card-bg:
      linear-gradient(180deg, rgb(255 255 255 / 92%), rgb(248 250 252 / 90%)),
      radial-gradient(circle at top right, rgb(93 135 255 / 14%), transparent 36%);
    --preview-card-shadow: 0 12px 26px rgb(15 23 42 / 6%);
    --preview-surface-border: var(--el-border-color-lighter);
    --preview-surface-bg: var(--el-fill-color-light);
    --preview-menu-border: color-mix(in srgb, var(--el-color-primary) 12%, var(--el-border-color));
    --preview-menu-bg:
      linear-gradient(135deg, rgb(255 255 255 / 96%), rgb(244 248 255 / 98%)),
      radial-gradient(circle at top right, rgb(93 135 255 / 10%), transparent 42%);
    --preview-menu-color: var(--art-gray-800);
    --preview-menu-shadow: inset 0 1px 0 rgb(255 255 255 / 75%), 0 10px 24px rgb(15 23 42 / 6%);
    display: flex;
    flex-direction: column;
    gap: 18px;
  }

  .system-setting-preview.is-dark {
    --preview-card-border: color-mix(in srgb, var(--el-color-primary) 18%, var(--default-border));
    --preview-card-bg:
      linear-gradient(180deg, rgb(25 27 34 / 96%), rgb(18 20 26 / 98%)),
      radial-gradient(circle at top right, rgb(93 135 255 / 20%), transparent 40%);
    --preview-card-shadow: 0 18px 34px rgb(0 0 0 / 28%);
    --preview-surface-border: color-mix(
      in srgb,
      var(--el-color-primary) 14%,
      var(--default-border)
    );
    --preview-surface-bg: rgb(255 255 255 / 4%);
    --preview-menu-border: color-mix(in srgb, var(--el-color-primary) 24%, var(--default-border));
    --preview-menu-bg:
      linear-gradient(135deg, rgb(28 31 39 / 98%), rgb(18 20 26 / 98%)),
      radial-gradient(circle at top right, rgb(93 135 255 / 22%), transparent 46%);
    --preview-menu-color: var(--art-gray-900);
    --preview-menu-shadow: inset 0 1px 0 rgb(255 255 255 / 6%), 0 16px 30px rgb(0 0 0 / 30%);
  }

  .system-setting-preview__block {
    padding: 16px;
    border: 1px solid var(--preview-card-border);
    border-radius: 16px;
    background: var(--preview-card-bg);
    box-shadow: var(--preview-card-shadow);
    transition:
      background 0.2s ease,
      border-color 0.2s ease,
      box-shadow 0.2s ease;
  }

  .system-setting-preview__label {
    margin-bottom: 12px;
    font-size: 13px;
    color: var(--art-gray-500);
  }

  .system-setting-preview__hero,
  .system-setting-preview__menu,
  .system-setting-preview__favicon {
    display: flex;
    align-items: center;
    gap: 12px;
  }

  .system-setting-preview__logo,
  .system-setting-preview__menu-logo {
    width: 44px;
    height: 44px;
    object-fit: cover;
    border: 1px solid var(--preview-surface-border);
    border-radius: 12px;
    background: var(--preview-surface-bg);
    transition:
      background 0.2s ease,
      border-color 0.2s ease;
  }

  .system-setting-preview__favicon-icon {
    width: 18px;
    height: 18px;
    object-fit: cover;
    border-radius: 4px;
  }

  .system-setting-preview__menu {
    padding: 14px 16px;
    border: 1px solid var(--preview-menu-border);
    border-radius: 14px;
    color: var(--preview-menu-color);
    background: var(--preview-menu-bg);
    box-shadow: var(--preview-menu-shadow);
    transition:
      background 0.2s ease,
      border-color 0.2s ease,
      box-shadow 0.2s ease,
      color 0.2s ease;
  }

  .system-setting-preview__home {
    display: flex;
    flex-direction: column;
    gap: 12px;
    margin-top: 14px;
  }

  .system-setting-preview__home-title {
    font-size: 18px;
    font-weight: 600;
    color: var(--preview-menu-color);
  }

  .system-setting-preview__status {
    display: inline-flex;
    align-items: center;
    width: fit-content;
    padding: 6px 12px;
    border-radius: 999px;
    color: var(--el-color-success);
    background: color-mix(in srgb, var(--el-color-success) 12%, transparent);
    font-size: 12px;
    font-weight: 600;
  }

  .system-setting-preview__status.is-disabled {
    color: var(--el-color-danger);
    background: color-mix(in srgb, var(--el-color-danger) 12%, transparent);
  }

  .system-setting-preview__home-desc {
    font-size: 13px;
    line-height: 1.75;
    color: var(--art-gray-500);
    white-space: pre-line;
  }

  .system-setting-preview__disabled {
    margin-top: 14px;
    font-size: 13px;
    line-height: 1.8;
    color: var(--art-gray-500);
  }

  .system-setting-preview__button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: fit-content;
    min-width: 128px;
    height: 40px;
    padding: 0 18px;
    border-radius: 999px;
    color: #fff;
    background: linear-gradient(135deg, var(--el-color-primary), #3e63dd);
    box-shadow: 0 12px 24px rgb(93 135 255 / 24%);
  }
</style>
