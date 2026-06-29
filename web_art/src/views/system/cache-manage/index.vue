<template>
  <div class="cache-manage-page art-full-height">
    <div class="cache-manage-page__hero">
      <div>
        <div class="text-2xl font-semibold">缓存管理</div>
        <div class="mt-2 text-sm text-(--art-gray-500)">
          这里提供当前浏览器缓存、字典缓存和安全运行缓存的清理入口。
        </div>
      </div>

      <ElButton v-auth="'system:cache:index'" :loading="loading" @click="loadOverview"
        >刷新概览</ElButton
      >
    </div>

    <ElAlert
      class="mb-4"
      title="运行缓存清理会保留 JWT 黑名单和登录失败限制缓存，避免误伤注销安全和登录风控。"
      type="warning"
      :closable="false"
    />

    <div class="grid gap-4 xl:grid-cols-2">
      <ElCard class="cache-manage-card xl:col-span-2">
        <template #header>
          <div class="cache-manage-card__header">
            <div>
              <div class="cache-manage-card__title">缓存驱动</div>
              <div class="cache-manage-card__desc">
                默认使用文件缓存，切换 Redis 前会校验连接参数。
              </div>
            </div>
            <div class="cache-manage-card__metrics">
              <span>当前 {{ cacheSetting.driver || '-' }}</span>
              <span :class="{ 'is-danger': !cacheSetting.health.available }">
                {{ cacheSetting.health.available ? '可用' : '异常' }}
              </span>
            </div>
          </div>
        </template>

        <div class="cache-manage-card__body">
          <ElForm class="cache-setting-form" :model="cacheSetting" label-position="top">
            <ElFormItem label="缓存驱动">
              <ElRadioGroup v-model="cacheSetting.driver">
                <ElRadioButton label="file">文件缓存</ElRadioButton>
                <ElRadioButton label="redis">Redis</ElRadioButton>
              </ElRadioGroup>
            </ElFormItem>

            <div v-if="cacheSetting.driver === 'redis'" class="cache-setting-form__grid">
              <ElFormItem label="主机">
                <ElInput v-model="cacheSetting.redis.host" placeholder="127.0.0.1" />
              </ElFormItem>
              <ElFormItem label="端口">
                <ElInputNumber
                  v-model="cacheSetting.redis.port"
                  :min="1"
                  :max="65535"
                  controls-position="right"
                />
              </ElFormItem>
              <ElFormItem label="数据库">
                <ElInputNumber
                  v-model="cacheSetting.redis.select"
                  :min="0"
                  :max="255"
                  controls-position="right"
                />
              </ElFormItem>
              <ElFormItem label="超时秒数">
                <ElInputNumber
                  v-model="cacheSetting.redis.timeout"
                  :min="0"
                  :max="60"
                  controls-position="right"
                />
              </ElFormItem>
              <ElFormItem label="Key 前缀">
                <ElInput v-model="cacheSetting.redis.prefix" placeholder="light_cache:" />
              </ElFormItem>
              <ElFormItem label="默认有效期">
                <ElInputNumber
                  v-model="cacheSetting.redis.expire"
                  :min="0"
                  :max="315360000"
                  controls-position="right"
                />
              </ElFormItem>
              <ElFormItem label="密码">
                <ElInput
                  v-model="cacheSetting.redis.password"
                  :disabled="cacheSetting.redis.clear_password"
                  show-password
                  placeholder="留空保留原密码"
                />
                <div v-if="cacheSetting.redis.password_set" class="cache-setting-form__hint">
                  已配置密码
                </div>
              </ElFormItem>
              <ElFormItem label="连接方式">
                <div class="cache-setting-form__switches">
                  <ElSwitch
                    v-model="cacheSetting.redis.persistent"
                    active-text="持久连接"
                    inactive-text="短连接"
                  />
                  <ElCheckbox
                    v-model="cacheSetting.redis.clear_password"
                    :disabled="!cacheSetting.redis.password_set"
                  >
                    清空密码
                  </ElCheckbox>
                </div>
              </ElFormItem>
            </div>
          </ElForm>

          <div
            v-if="!cacheSetting.health.available"
            class="cache-manage-card__note cache-manage-card__note--danger"
          >
            {{ cacheSetting.health.message || '当前缓存驱动不可用，请检查配置。' }}
          </div>

          <div class="cache-manage-card__action">
            <ElButton
              class="cache-manage-card__button"
              type="primary"
              :loading="settingLoading"
              v-auth="'system:cache:index'"
              @click="handleSaveCacheSetting"
              v-ripple
            >
              保存缓存配置
            </ElButton>
          </div>
        </div>
      </ElCard>

      <ElCard class="cache-manage-card">
        <template #header>
          <div class="cache-manage-card__header">
            <div>
              <div class="cache-manage-card__title">当前浏览器缓存</div>
              <div class="cache-manage-card__desc">只影响当前设备，执行后会退出登录。</div>
            </div>
            <ElTag type="info">当前设备</ElTag>
          </div>
        </template>

        <div class="cache-manage-card__body">
          <div class="cache-manage-card__note">
            适合处理系统名称、Logo、Favicon 更新后当前浏览器仍显示旧数据的情况。
          </div>

          <div class="cache-manage-card__action">
            <ElButton
              class="cache-manage-card__button"
              type="primary"
              @click="handleClearBrowserCache"
              v-ripple
            >
              清缓存
            </ElButton>
          </div>
        </div>
      </ElCard>

      <ElCard class="cache-manage-card">
        <template #header>
          <div class="cache-manage-card__header">
            <div>
              <div class="cache-manage-card__title">字典缓存</div>
              <div class="cache-manage-card__desc"
                >刷新后会重建字典缓存，适合字典修改后立即生效。</div
              >
            </div>
            <div class="cache-manage-card__metrics">
              <span>类型 {{ overview.dict.type_count }}</span>
              <span>已缓存 {{ overview.dict.cached_count }}</span>
            </div>
          </div>
        </template>

        <div class="cache-manage-card__body">
          <div class="cache-manage-card__note">
            会按当前字典数据重新生成缓存，不影响登录状态和安全缓存。
          </div>

          <div class="cache-manage-card__action">
            <ElButton
              class="cache-manage-card__button"
              type="primary"
              :loading="dictLoading"
              v-auth="'system:cache:refreshdict'"
              @click="handleRefreshDictCache"
              v-ripple
            >
              刷新字典缓存
            </ElButton>
          </div>
        </div>
      </ElCard>

      <ElCard class="cache-manage-card xl:col-span-2">
        <template #header>
          <div class="cache-manage-card__header">
            <div>
              <div class="cache-manage-card__title">运行缓存</div>
              <div class="cache-manage-card__desc">
                定向清理文件缓存，保留 JWT 黑名单和登录失败限制缓存。
              </div>
            </div>
            <div class="cache-manage-card__metrics">
              <span>文件 {{ overview.runtime.file_count }}</span>
              <span>体积 {{ formatBytes(overview.runtime.size_bytes) }}</span>
              <span>保留 {{ overview.runtime.protected_file_count }}</span>
            </div>
          </div>
        </template>

        <div class="cache-manage-card__body">
          <div class="cache-manage-card__runtime-meta">
            <div>
              <span class="cache-manage-card__meta-label">驱动</span>
              <span>{{ overview.runtime.driver || '-' }}</span>
            </div>
            <div>
              <span class="cache-manage-card__meta-label">目录</span>
              <span class="cache-manage-card__path">{{ overview.runtime.path || '-' }}</span>
            </div>
          </div>

          <div class="cache-manage-card__note">
            <template v-if="overview.runtime.supported">
              会清掉运行时文件缓存中的普通缓存项，适合排查缓存滞留、配置更新不一致等问题。
            </template>
            <template v-else> 当前缓存驱动不是文件缓存，暂不支持运行缓存清理。 </template>
          </div>

          <div class="cache-manage-card__action">
            <ElButton
              class="cache-manage-card__button"
              type="danger"
              :disabled="!overview.runtime.supported"
              :loading="runtimeLoading"
              v-auth="'system:cache:clearruntime'"
              @click="handleClearRuntimeCache"
              v-ripple
            >
              清理运行缓存
            </ElButton>
          </div>
        </div>
      </ElCard>
    </div>
  </div>
</template>

<script setup lang="ts">
  import { ElAlert, ElMessageBox, ElTag } from 'element-plus'
  import {
    fetchClearRuntimeCache,
    fetchGetCacheOverview,
    fetchRefreshDictCache,
    fetchSaveCacheSetting
  } from '@/api/system-manage'
  import { clearBrowserCacheAndLogout } from '@/utils/auth/session-actions'

  defineOptions({ name: 'CacheManage' })

  const loading = ref(false)
  const settingLoading = ref(false)
  const dictLoading = ref(false)
  const runtimeLoading = ref(false)

  const createDefaultCacheSetting = (): Api.SystemManage.CacheSetting => ({
    driver: 'file',
    drivers: ['file', 'redis'],
    redis: {
      host: '127.0.0.1',
      port: 6379,
      password: '',
      password_set: false,
      clear_password: false,
      select: 0,
      timeout: 3,
      persistent: false,
      prefix: 'light_cache:',
      expire: 0
    },
    health: {
      available: true,
      message: ''
    }
  })

  const cacheSetting = reactive<Api.SystemManage.CacheSetting>(createDefaultCacheSetting())

  const overview = reactive<Api.SystemManage.CacheOverview>({
    browser: {
      scope: 'current_browser'
    },
    dict: {
      type_count: 0,
      cached_count: 0
    },
    runtime: {
      supported: false,
      driver: '',
      path: '',
      file_count: 0,
      size_bytes: 0,
      protected_file_count: 0
    },
    setting: createDefaultCacheSetting()
  })

  const assignOverview = (data: Api.SystemManage.CacheOverview) => {
    overview.browser = data.browser
    overview.dict = data.dict
    overview.runtime = data.runtime
    overview.setting = data.setting
    assignCacheSetting(data.setting)
  }

  const assignCacheSetting = (setting?: Api.SystemManage.CacheSetting) => {
    const nextSetting = setting || createDefaultCacheSetting()

    cacheSetting.driver = nextSetting.driver || 'file'
    cacheSetting.drivers = nextSetting.drivers?.length ? nextSetting.drivers : ['file', 'redis']
    cacheSetting.redis = {
      ...createDefaultCacheSetting().redis,
      ...nextSetting.redis,
      password: '',
      clear_password: false
    }
    cacheSetting.health = nextSetting.health || {
      available: true,
      message: ''
    }
  }

  const buildCacheSettingPayload = (): Api.SystemManage.CacheSettingPayload => ({
    driver: cacheSetting.driver,
    redis: {
      host: cacheSetting.redis.host,
      port: cacheSetting.redis.port,
      password: cacheSetting.redis.password,
      clear_password: cacheSetting.redis.clear_password,
      select: cacheSetting.redis.select,
      timeout: cacheSetting.redis.timeout,
      persistent: cacheSetting.redis.persistent,
      prefix: cacheSetting.redis.prefix,
      expire: cacheSetting.redis.expire
    }
  })

  const handleSaveCacheSetting = async () => {
    const message =
      cacheSetting.driver === 'redis'
        ? '保存前会先测试 Redis 连接，确认保存当前缓存配置吗？'
        : '确认切换为文件缓存吗？'

    await ElMessageBox.confirm(message, '保存缓存配置', {
      type: 'warning',
      confirmButtonText: '确定',
      cancelButtonText: '取消'
    })

    settingLoading.value = true

    try {
      await fetchSaveCacheSetting(buildCacheSettingPayload())
      await loadOverview()
    } finally {
      settingLoading.value = false
    }
  }

  const formatBytes = (bytes: number) => {
    if (!bytes) {
      return '0 B'
    }

    const units = ['B', 'KB', 'MB', 'GB']
    let value = bytes
    let index = 0

    while (value >= 1024 && index < units.length - 1) {
      value /= 1024
      index += 1
    }

    return `${value >= 10 || index === 0 ? value.toFixed(0) : value.toFixed(1)} ${units[index]}`
  }

  const loadOverview = async () => {
    loading.value = true

    try {
      const data = await fetchGetCacheOverview()
      assignOverview(data)
    } finally {
      loading.value = false
    }
  }

  const handleClearBrowserCache = async () => {
    await ElMessageBox.confirm(
      '清理当前浏览器缓存后会退出登录，需要重新登录后继续使用，是否继续？',
      '清缓存',
      {
        type: 'warning',
        confirmButtonText: '确定',
        cancelButtonText: '取消'
      }
    )

    await clearBrowserCacheAndLogout()
  }

  const handleRefreshDictCache = async () => {
    await ElMessageBox.confirm(
      '确认刷新字典缓存吗？刷新后字典相关页面会立即使用最新缓存。',
      '刷新字典缓存',
      {
        type: 'warning',
        confirmButtonText: '确定',
        cancelButtonText: '取消'
      }
    )

    dictLoading.value = true

    try {
      await fetchRefreshDictCache()
      await loadOverview()
    } finally {
      dictLoading.value = false
    }
  }

  const handleClearRuntimeCache = async () => {
    await ElMessageBox.confirm(
      '确认清理运行缓存吗？本次会保留 JWT 黑名单和登录失败限制缓存。',
      '清理运行缓存',
      {
        type: 'warning',
        confirmButtonText: '确定',
        cancelButtonText: '取消'
      }
    )

    runtimeLoading.value = true

    try {
      await fetchClearRuntimeCache()
      await loadOverview()
    } finally {
      runtimeLoading.value = false
    }
  }

  onMounted(() => {
    loadOverview()
  })
</script>

<style scoped lang="scss">
  .cache-manage-page {
    display: flex;
    flex-direction: column;
    gap: 16px;
  }

  .cache-manage-page__hero {
    display: flex;
    gap: 16px;
    align-items: flex-start;
    justify-content: space-between;
    padding: 24px;
    color: var(--art-gray-900);
    background:
      linear-gradient(
        145deg,
        color-mix(in srgb, var(--el-bg-color) 96%, var(--el-color-primary) 4%),
        color-mix(in srgb, var(--el-bg-color) 94%, var(--el-fill-color-light))
      ),
      radial-gradient(
        circle at top right,
        color-mix(in srgb, var(--el-color-primary) 12%, transparent),
        transparent 36%
      );
    border: 1px solid var(--el-border-color-lighter);
    border-radius: 18px;
    box-shadow: 0 16px 36px rgb(0 0 0 / 8%);
  }

  .cache-manage-card {
    min-height: 100%;
  }

  .cache-manage-card__header {
    display: flex;
    gap: 16px;
    align-items: flex-start;
    justify-content: space-between;
  }

  .cache-manage-card__title {
    font-size: 16px;
    font-weight: 600;
    color: var(--art-gray-800);
  }

  .cache-manage-card__desc {
    margin-top: 6px;
    font-size: 13px;
    line-height: 1.7;
    color: var(--art-gray-500);
  }

  .cache-manage-card__metrics {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    justify-content: flex-end;

    span {
      display: inline-flex;
      align-items: center;
      height: 28px;
      padding: 0 10px;
      font-size: 12px;
      font-weight: 600;
      color: var(--el-color-primary);
      white-space: nowrap;
      background: color-mix(in srgb, var(--el-color-primary) 8%, transparent);
      border-radius: 999px;

      &.is-danger {
        color: var(--el-color-danger);
        background: color-mix(in srgb, var(--el-color-danger) 10%, transparent);
      }
    }
  }

  .cache-manage-card__body {
    display: flex;
    flex-direction: column;
    gap: 18px;
  }

  .cache-manage-card__action {
    display: flex;
    justify-content: center;
  }

  .cache-manage-card__button {
    min-width: 0;
    padding: 0 14px;
  }

  .cache-manage-card__note {
    padding: 14px 16px;
    font-size: 13px;
    line-height: 1.8;
    color: var(--art-gray-600);
    background: var(--el-fill-color-light);
    border-radius: 14px;
  }

  .cache-manage-card__note--danger {
    color: var(--el-color-danger);
    background: color-mix(in srgb, var(--el-color-danger) 10%, transparent);
  }

  .cache-setting-form {
    :deep(.el-input-number) {
      width: 100%;
    }
  }

  .cache-setting-form__grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 12px 16px;
  }

  .cache-setting-form__hint {
    margin-top: 6px;
    font-size: 12px;
    color: var(--art-gray-500);
  }

  .cache-setting-form__switches {
    display: flex;
    flex-wrap: wrap;
    gap: 12px 18px;
    align-items: center;
    min-height: 32px;
  }

  .cache-manage-card__runtime-meta {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 12px;

    > div {
      display: flex;
      flex-direction: column;
      gap: 8px;
      padding: 14px 16px;
      font-size: 13px;
      color: var(--art-gray-700);
      background: var(--el-fill-color-blank);
      border: 1px solid var(--el-border-color-lighter);
      border-radius: 14px;
    }
  }

  .cache-manage-card__meta-label {
    font-size: 12px;
    color: var(--art-gray-500);
  }

  .cache-manage-card__path {
    line-height: 1.7;
    word-break: break-all;
  }

  @media (width <= 768px) {
    .cache-manage-page__hero {
      flex-direction: column;
      align-items: stretch;
    }

    .cache-manage-card__header {
      flex-direction: column;
    }

    .cache-manage-card__metrics {
      justify-content: flex-start;
    }
  }
</style>
