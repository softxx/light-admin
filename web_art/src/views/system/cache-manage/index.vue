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
    fetchRefreshDictCache
  } from '@/api/system-manage'
  import { clearBrowserCacheAndLogout } from '@/utils/auth/session-actions'

  defineOptions({ name: 'CacheManage' })

  const loading = ref(false)
  const dictLoading = ref(false)
  const runtimeLoading = ref(false)

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
    }
  })

  const assignOverview = (data: Api.SystemManage.CacheOverview) => {
    overview.browser = data.browser
    overview.dict = data.dict
    overview.runtime = data.runtime
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
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
    padding: 24px;
    border: 1px solid var(--el-border-color-lighter);
    border-radius: 18px;
    background:
      linear-gradient(145deg, rgb(255 255 255 / 98%), rgb(247 249 252 / 95%)),
      radial-gradient(circle at top right, rgb(14 116 144 / 10%), transparent 36%);
    box-shadow: 0 16px 36px rgb(15 23 42 / 5%);
  }

  .cache-manage-card {
    min-height: 100%;
  }

  .cache-manage-card__header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
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
    justify-content: flex-end;
    gap: 8px;

    span {
      display: inline-flex;
      align-items: center;
      height: 28px;
      padding: 0 10px;
      border-radius: 999px;
      background: color-mix(in srgb, var(--el-color-primary) 8%, transparent);
      color: var(--el-color-primary);
      font-size: 12px;
      font-weight: 600;
      white-space: nowrap;
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
    border-radius: 14px;
    background: var(--el-fill-color-light);
    color: var(--art-gray-600);
    font-size: 13px;
    line-height: 1.8;
  }

  .cache-manage-card__runtime-meta {
    display: grid;
    gap: 12px;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));

    > div {
      display: flex;
      flex-direction: column;
      gap: 8px;
      padding: 14px 16px;
      border: 1px solid var(--el-border-color-lighter);
      border-radius: 14px;
      background: #fff;
      color: var(--art-gray-700);
      font-size: 13px;
    }
  }

  .cache-manage-card__meta-label {
    color: var(--art-gray-500);
    font-size: 12px;
  }

  .cache-manage-card__path {
    word-break: break-all;
    line-height: 1.7;
  }

  @media (max-width: 768px) {
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
