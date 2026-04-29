<template>
  <div class="version-manage-page art-full-height">
    <div class="version-manage-page__header">
      <div>
        <div class="text-2xl font-semibold">版本管理</div>
        <div class="mt-2 text-sm text-(--art-gray-500)">
          当前通过可切换发布源检查新版本，升级任务会在服务端后台执行。
        </div>
      </div>

      <div class="version-manage-page__actions">
        <ElButton
          v-auth="'system:version:current'"
          :icon="Refresh"
          :loading="loading"
          @click="loadInitialData"
        >
          刷新
        </ElButton>
        <ElButton
          v-auth="'system:version:check'"
          :icon="Search"
          type="primary"
          :loading="checking"
          @click="handleCheck"
        >
          检查更新
        </ElButton>
      </div>
    </div>

    <div class="version-manage-page__source">
      <ElForm :model="releaseForm" label-width="96px">
        <ElRow :gutter="12">
          <ElCol :xs="24" :md="8">
            <ElFormItem label="发布源">
              <ElSelect v-model="releaseForm.source">
                <ElOption label="GitHub Releases" value="github" />
                <ElOption label="GitLab Releases" value="gitlab" />
                <ElOption label="Gitee Releases" value="gitee" />
                <ElOption label="腾讯 CNB Releases" value="cnb" />
              </ElSelect>
            </ElFormItem>
          </ElCol>
          <ElCol :xs="24" :md="8">
            <ElFormItem
              v-if="usesProjectField"
              :label="releaseForm.source === 'cnb' ? '仓库路径' : 'Project'"
            >
              <ElInput
                v-model.trim="releaseForm.project"
                clearable
                :placeholder="projectPlaceholder"
              />
            </ElFormItem>
            <ElFormItem v-else label="Owner">
              <ElInput v-model.trim="releaseForm.owner" clearable placeholder="用户或组织" />
            </ElFormItem>
          </ElCol>
          <ElCol v-if="!usesProjectField" :xs="24" :md="8">
            <ElFormItem label="Repo">
              <ElInput v-model.trim="releaseForm.repo" clearable placeholder="仓库名" />
            </ElFormItem>
          </ElCol>
          <ElCol :xs="24" :md="8">
            <ElFormItem label="资源规则">
              <ElInput
                v-model.trim="releaseForm.asset_pattern"
                clearable
                placeholder="light-admin-{version}.zip"
              />
            </ElFormItem>
          </ElCol>
          <ElCol :xs="24" :md="8">
            <ElFormItem label="预发布版">
              <ElSwitch v-model="releaseForm.include_prerelease" />
            </ElFormItem>
          </ElCol>
        </ElRow>
      </ElForm>
    </div>

    <div class="version-manage-page__grid">
      <ElCard class="version-card">
        <template #header>
          <div class="version-card__title">当前版本</div>
        </template>

        <ElDescriptions :column="1" border>
          <ElDescriptionsItem label="版本号">{{ current?.version || '-' }}</ElDescriptionsItem>
          <ElDescriptionsItem label="构建号">{{ current?.build || '-' }}</ElDescriptionsItem>
          <ElDescriptionsItem label="发布通道">{{ current?.channel || '-' }}</ElDescriptionsItem>
          <ElDescriptionsItem label="PHP">{{
            currentData?.environment?.php_version || '-'
          }}</ElDescriptionsItem>
          <ElDescriptionsItem label="ThinkPHP">
            {{ currentData?.environment?.thinkphp_version || '-' }}
          </ElDescriptionsItem>
        </ElDescriptions>
      </ElCard>

      <ElCard class="version-card">
        <template #header>
          <div class="version-card__title">最新版本</div>
        </template>

        <div v-if="latestVersion" class="version-latest">
          <div class="version-latest__main">
            <div>
              <div class="version-latest__number">{{ latestVersion.version }}</div>
              <div class="version-latest__meta">
                {{ latestVersion.tag_name || '-' }} /
                {{ formatReleaseTime(latestVersion.released_at) }}
              </div>
            </div>
            <ElTag :type="upgradeAvailable ? 'success' : 'info'">
              {{ upgradeAvailable ? '可升级' : '已是最新' }}
            </ElTag>
          </div>

          <div class="version-latest__tags">
            <ElTag v-if="latestVersion.required" type="danger">强制升级</ElTag>
            <ElTag v-if="latestVersion.database_migration" type="warning">包含数据库迁移</ElTag>
            <ElTag v-if="latestVersion.php" type="info">PHP {{ latestVersion.php }}</ElTag>
            <ElTag v-if="latestVersion.asset_name" type="info">{{
              latestVersion.asset_name
            }}</ElTag>
          </div>

          <ElLink
            v-if="latestVersion.release_url"
            :href="latestVersion.release_url"
            target="_blank"
            type="primary"
          >
            查看发布页
          </ElLink>

          <ul v-if="latestVersion.release_notes?.length" class="version-latest__notes">
            <li v-for="note in latestVersion.release_notes" :key="note">{{ note }}</li>
          </ul>
        </div>

        <ElEmpty v-else description="尚未检查更新" :image-size="88" />
      </ElCard>
    </div>

    <ElCard class="version-card">
      <template #header>
        <div class="version-card__toolbar">
          <div class="version-card__title">升级操作</div>
          <div class="version-card__buttons">
            <ElButton
              v-auth="'system:version:download'"
              :icon="Download"
              :disabled="!latestVersion"
              :loading="downloading"
              @click="handleDownload"
            >
              下载
            </ElButton>
            <ElButton
              v-auth="'system:version:precheck'"
              :icon="CircleCheck"
              :disabled="!latestVersion"
              :loading="prechecking"
              @click="handlePrecheck"
            >
              预检查
            </ElButton>
            <ElButton
              v-auth="'system:version:upgrade'"
              :icon="Promotion"
              type="primary"
              :disabled="!canStartUpgrade"
              :loading="upgrading"
              @click="handleUpgrade"
            >
              一键升级
            </ElButton>
            <ElButton
              v-auth="'system:version:rollback'"
              :icon="RefreshLeft"
              type="warning"
              plain
              :disabled="!rollbackTask"
              :loading="rollingBack"
              @click="handleRollback"
            >
              回滚
            </ElButton>
          </div>
        </div>
      </template>

      <div class="version-operation">
        <div class="version-operation__summary">
          <div>
            <span>升级包</span>
            <strong>{{ packageInfo?.package_path || latestVersion?.asset_name || '-' }}</strong>
          </div>
          <div>
            <span>包大小</span>
            <strong>{{
              formatBytes(packageInfo?.size_bytes || latestVersion?.size_bytes || 0)
            }}</strong>
          </div>
          <div>
            <span>SHA256</span>
            <strong>{{ packageSha256Text }}</strong>
          </div>
        </div>

        <ElTable v-if="precheckResult" :data="precheckResult.checks" border>
          <ElTableColumn prop="title" label="检查项" min-width="160" />
          <ElTableColumn label="状态" width="100">
            <template #default="{ row }">
              <ElTag :type="precheckTagType(row.status)">
                {{ precheckStatusText(row.status) }}
              </ElTag>
            </template>
          </ElTableColumn>
          <ElTableColumn prop="message" label="结果" min-width="260" show-overflow-tooltip />
        </ElTable>

        <ElAlert
          v-else
          title="升级前会再次下载、校验和预检查，确认后系统会短暂进入维护模式。"
          type="info"
          :closable="false"
        />
      </div>
    </ElCard>

    <div class="version-manage-page__grid version-manage-page__grid--task">
      <ElCard class="version-card">
        <template #header>
          <div class="version-card__title">任务进度</div>
        </template>

        <template v-if="activeTask">
          <div class="version-task">
            <div class="version-task__head">
              <div>
                <div class="version-task__version">目标版本 {{ activeTask.target_version }}</div>
                <div class="version-task__message">{{ activeTask.message || '-' }}</div>
              </div>
              <ElTag :type="taskTagType(activeTask.status)">
                {{ taskStatusText(activeTask.status) }}
              </ElTag>
            </div>

            <ElProgress :percentage="activeTask.progress || 0" :status="progressStatus" />

            <div class="version-task__logs">
              <div
                v-for="item in activeTask.logs || []"
                :key="`${item.time}-${item.message}`"
                class="version-task__log"
                :class="`is-${item.level}`"
              >
                <span>{{ item.time }}</span>
                <strong>{{ item.message }}</strong>
              </div>
            </div>
          </div>
        </template>

        <ElEmpty v-else description="暂无运行中的升级任务" :image-size="88" />
      </ElCard>

      <ElCard class="version-card">
        <template #header>
          <div class="version-card__title">升级记录</div>
        </template>

        <ElTable :data="tasks" border height="360" @row-click="handleSelectTask">
          <ElTableColumn prop="id" label="ID" width="72" />
          <ElTableColumn prop="target_version" label="版本" min-width="110" />
          <ElTableColumn label="状态" width="110">
            <template #default="{ row }">
              <ElTag :type="taskTagType(row.status)">
                {{ taskStatusText(row.status) }}
              </ElTag>
            </template>
          </ElTableColumn>
          <ElTableColumn prop="message" label="消息" min-width="180" show-overflow-tooltip />
          <ElTableColumn label="创建时间" min-width="150">
            <template #default="{ row }">{{ formatTime(row.create_time) }}</template>
          </ElTableColumn>
        </ElTable>
      </ElCard>
    </div>
  </div>
</template>

<script setup lang="ts">
  import {
    CircleCheck,
    Download,
    Promotion,
    Refresh,
    RefreshLeft,
    Search
  } from '@element-plus/icons-vue'
  import { ElMessage, ElMessageBox } from 'element-plus'
  import {
    fetchCheckVersion,
    fetchDownloadVersionPackage,
    fetchGetVersionCurrent,
    fetchGetVersionTask,
    fetchGetVersionTasks,
    fetchPrecheckVersion,
    fetchRollbackVersionTask,
    fetchStartVersionUpgrade
  } from '@/api/system-manage'

  defineOptions({ name: 'VersionManage' })

  // 页面级加载状态分开维护，避免下载、预检和升级互相锁住按钮。
  const loading = ref(false)
  const checking = ref(false)
  const downloading = ref(false)
  const prechecking = ref(false)
  const upgrading = ref(false)
  const rollingBack = ref(false)

  // 发布源参数允许从后端配置带入，也允许超级管理员临时调整后检查。
  const releaseForm = reactive({
    source: 'github',
    owner: '',
    repo: '',
    project: '',
    asset_pattern: 'light-admin-{version}.zip',
    include_prerelease: false
  })

  // 版本中心状态：检查结果、升级包、预检查结果和任务列表。
  const currentData = ref<Api.SystemManage.VersionCurrentResponse>()
  const checkResult = ref<Api.SystemManage.VersionCheckResponse>()
  const packageInfo = ref<Api.SystemManage.VersionDownloadResponse>()
  const precheckResult = ref<Api.SystemManage.VersionPrecheckResponse>()
  const activeTask = ref<Api.SystemManage.UpgradeTask>()
  const tasks = ref<Api.SystemManage.UpgradeTask[]>([])
  const pollTimer = ref<number>()

  const current = computed(() => currentData.value?.current)
  const latestVersion = computed(() => checkResult.value?.latest)
  const upgradeAvailable = computed(() => Boolean(checkResult.value?.upgrade_available))
  const usesProjectField = computed(() => ['gitlab', 'cnb'].includes(releaseForm.source))
  const projectPlaceholder = computed(() =>
    releaseForm.source === 'cnb' ? '组织/仓库，例如 team/light-admin' : 'group/project 或项目 ID'
  )
  const canStartUpgrade = computed(() => {
    if (!latestVersion.value || !upgradeAvailable.value) {
      return false
    }

    if (precheckResult.value) {
      return precheckResult.value.can_upgrade
    }

    return true
  })
  const rollbackTask = computed(() =>
    tasks.value.find((item) => item.status === 'success' && item.backup_path)
  )
  const progressStatus = computed(() => {
    if (!activeTask.value) return undefined
    if (activeTask.value.status === 'success' || activeTask.value.status === 'rolled_back')
      return 'success'
    if (activeTask.value.status === 'failed' || activeTask.value.status === 'rollback_failed')
      return 'exception'
    return undefined
  })
  const packageSha256Text = computed(() => {
    const verification = packageInfo.value?.verification
    if (verification?.digest_checked) {
      return verification.sha256_matched ? '已校验' : '校验失败'
    }

    if (verification?.sha256) {
      return '已计算'
    }

    return latestVersion.value?.sha256 ? '待下载校验' : '未提供'
  })

  const terminalStatuses = ['success', 'failed', 'rolled_back', 'rollback_failed']

  const releaseParams = () => ({
    source: releaseForm.source,
    owner: releaseForm.owner,
    repo: releaseForm.repo,
    project: releaseForm.project,
    asset_pattern: releaseForm.asset_pattern,
    include_prerelease: releaseForm.include_prerelease
  })

  // 首次进入页面时拉取当前版本和最近任务，如果任务仍在运行则恢复轮询。
  const loadInitialData = async () => {
    loading.value = true
    try {
      const [currentResponse, taskList] = await Promise.all([
        fetchGetVersionCurrent(),
        fetchGetVersionTasks(20)
      ])
      currentData.value = currentResponse
      tasks.value = taskList
      fillReleaseForm(currentResponse.release)

      const runningTask = taskList.find((task) => task.is_running)
      if (runningTask) {
        activeTask.value = runningTask
        startPolling(runningTask.id)
      } else if (currentResponse.last_task?.id) {
        activeTask.value = currentResponse.last_task
      }
    } finally {
      loading.value = false
    }
  }

  const fillReleaseForm = (release?: Api.SystemManage.VersionReleaseConfig) => {
    if (!release) return

    releaseForm.source = release.source || releaseForm.source
    releaseForm.owner = release.owner || releaseForm.owner
    releaseForm.repo = release.repo || releaseForm.repo
    releaseForm.project = release.project || releaseForm.project
    releaseForm.asset_pattern = release.asset_pattern || releaseForm.asset_pattern
    releaseForm.include_prerelease = Boolean(release.include_prerelease)
  }

  // 检查更新后清空上一轮下载和预检查结果，避免误用旧包路径。
  const handleCheck = async () => {
    checking.value = true
    try {
      checkResult.value = await fetchCheckVersion(releaseParams())
      packageInfo.value = undefined
      precheckResult.value = undefined
    } finally {
      checking.value = false
    }
  }

  // 下载会在后端完成发布源 sha256 校验，前端只展示校验结果。
  const handleDownload = async () => {
    if (!latestVersion.value) return

    downloading.value = true
    try {
      packageInfo.value = await fetchDownloadVersionPackage({
        ...releaseParams(),
        version: latestVersion.value.version
      })
    } finally {
      downloading.value = false
    }
  }

  // 页面预检查用于提前暴露环境问题；真正升级前 CLI 仍会再检查一次。
  const handlePrecheck = async () => {
    if (!latestVersion.value) return

    prechecking.value = true
    try {
      precheckResult.value = await fetchPrecheckVersion({
        ...releaseParams(),
        version: latestVersion.value.version,
        package_path: packageInfo.value?.package_path
      })
    } finally {
      prechecking.value = false
    }
  }

  // 一键升级只创建任务，实际升级由后端拉起 CLI 子进程完成。
  const handleUpgrade = async () => {
    if (!latestVersion.value) return

    await ElMessageBox.confirm(
      `确认升级到 ${latestVersion.value.version}？升级期间系统会进入维护模式。`,
      '启动升级',
      {
        type: 'warning',
        confirmButtonText: '确认升级',
        cancelButtonText: '取消'
      }
    )

    upgrading.value = true
    try {
      activeTask.value = await fetchStartVersionUpgrade({
        ...releaseParams(),
        version: latestVersion.value.version,
        package_path: packageInfo.value?.package_path
      })
      startPolling(activeTask.value.id)
      await refreshTasks()
    } finally {
      upgrading.value = false
    }
  }

  // 回滚复用升级任务的备份目录，执行期间同样进入维护模式。
  const handleRollback = async () => {
    if (!rollbackTask.value) return

    await ElMessageBox.confirm(`确认回滚版本 ${rollbackTask.value.target_version}？`, '回滚版本', {
      type: 'warning',
      confirmButtonText: '确认回滚',
      cancelButtonText: '取消'
    })

    rollingBack.value = true
    try {
      activeTask.value = await fetchRollbackVersionTask(rollbackTask.value.id)
      startPolling(activeTask.value.id)
      await refreshTasks()
    } finally {
      rollingBack.value = false
    }
  }

  const handleSelectTask = (row: Api.SystemManage.UpgradeTask) => {
    activeTask.value = row
    if (row.is_running) {
      startPolling(row.id)
    }
  }

  const refreshTasks = async () => {
    tasks.value = await fetchGetVersionTasks(20)
  }

  // 升级过程可能持续较久，使用轮询同步 CLI 任务进度和日志。
  const startPolling = (taskId: number) => {
    stopPolling()
    pollTask(taskId)
    pollTimer.value = window.setInterval(() => {
      pollTask(taskId)
    }, 2500)
  }

  const stopPolling = () => {
    if (pollTimer.value) {
      window.clearInterval(pollTimer.value)
      pollTimer.value = undefined
    }
  }

  const pollTask = async (taskId: number) => {
    const task = await fetchGetVersionTask(taskId)
    activeTask.value = task

    if (terminalStatuses.includes(task.status)) {
      stopPolling()
      await Promise.all([refreshTasks(), loadCurrentOnly()])
      if (task.status === 'success' || task.status === 'rolled_back') {
        ElMessage.success(task.message || '任务完成')
      }
    }
  }

  const loadCurrentOnly = async () => {
    currentData.value = await fetchGetVersionCurrent()
  }

  const taskStatusText = (status: Api.SystemManage.UpgradeTaskStatus) => {
    const map: Record<string, string> = {
      pending: '待执行',
      queued: '排队中',
      downloading: '下载中',
      verifying: '校验中',
      prechecking: '预检查',
      backing_up: '备份中',
      maintenance: '维护中',
      installing: '安装中',
      migrating: '迁移中',
      finishing: '收尾中',
      success: '成功',
      failed: '失败',
      rolling_back: '回滚中',
      rolled_back: '已回滚',
      rollback_failed: '回滚失败'
    }
    return map[status] || status
  }

  const taskTagType = (status: Api.SystemManage.UpgradeTaskStatus) => {
    if (status === 'success' || status === 'rolled_back') return 'success'
    if (status === 'failed' || status === 'rollback_failed') return 'danger'
    if (status === 'maintenance' || status === 'migrating' || status === 'rolling_back')
      return 'warning'
    return 'info'
  }

  const precheckStatusText = (status: string) => {
    if (status === 'pass') return '通过'
    if (status === 'warn') return '警告'
    return '失败'
  }

  const precheckTagType = (status: string) => {
    if (status === 'pass') return 'success'
    if (status === 'warn') return 'warning'
    return 'danger'
  }

  const formatBytes = (bytes: number) => {
    if (!bytes) return '0 B'
    const units = ['B', 'KB', 'MB', 'GB']
    let value = bytes
    let index = 0
    while (value >= 1024 && index < units.length - 1) {
      value /= 1024
      index += 1
    }
    return `${value >= 10 || index === 0 ? value.toFixed(0) : value.toFixed(1)} ${units[index]}`
  }

  const formatTime = (value?: number | string) => {
    if (!value) return '-'
    if (typeof value === 'number') {
      return new Date(value * 1000).toLocaleString()
    }
    return value
  }

  const formatReleaseTime = (value?: string) => {
    return value ? new Date(value).toLocaleString() : '-'
  }

  onMounted(() => {
    loadInitialData()
  })

  onBeforeUnmount(() => {
    stopPolling()
  })
</script>

<style scoped lang="scss">
  .version-manage-page {
    display: flex;
    flex-direction: column;
    gap: 16px;
  }

  .version-manage-page__header {
    display: flex;
    gap: 16px;
    align-items: flex-start;
    justify-content: space-between;
    padding: 20px;
    color: var(--art-gray-900);
    background:
      linear-gradient(
        145deg,
        color-mix(in srgb, var(--el-bg-color) 96%, var(--el-color-primary) 4%),
        var(--el-bg-color)
      ),
      radial-gradient(
        circle at top right,
        color-mix(in srgb, var(--el-color-primary) 10%, transparent),
        transparent 34%
      );
    border: 1px solid var(--el-border-color-lighter);
    border-radius: 8px;
    box-shadow: 0 12px 28px rgb(0 0 0 / 6%);
  }

  .version-manage-page__actions,
  .version-card__buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
  }

  .version-manage-page__source {
    padding: 14px 16px 0;
    background: var(--el-fill-color-blank);
    border: 1px solid var(--el-border-color-lighter);
    border-radius: 8px;
  }

  .version-manage-page__grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 16px;
  }

  .version-manage-page__grid--task {
    grid-template-columns: minmax(0, 0.95fr) minmax(0, 1.05fr);
  }

  .version-card {
    border-radius: 8px;
  }

  .version-card__title {
    font-size: 16px;
    font-weight: 600;
    color: var(--art-gray-800);
  }

  .version-card__toolbar,
  .version-latest__main,
  .version-task__head {
    display: flex;
    gap: 16px;
    align-items: flex-start;
    justify-content: space-between;
  }

  .version-latest {
    display: flex;
    flex-direction: column;
    gap: 14px;
  }

  .version-latest__number {
    font-size: 28px;
    font-weight: 700;
    line-height: 1.1;
    color: var(--art-gray-900);
  }

  .version-latest__meta {
    margin-top: 6px;
    font-size: 13px;
    color: var(--art-gray-500);
  }

  .version-latest__tags {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
  }

  .version-latest__notes {
    display: flex;
    flex-direction: column;
    gap: 8px;
    padding-left: 18px;
    margin: 0;
    font-size: 13px;
    line-height: 1.6;
    color: var(--art-gray-600);
  }

  .version-operation {
    display: flex;
    flex-direction: column;
    gap: 16px;
  }

  .version-operation__summary {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 12px;

    > div {
      display: flex;
      flex-direction: column;
      gap: 8px;
      min-width: 0;
      padding: 12px 14px;
      background: var(--el-fill-color-light);
      border: 1px solid var(--el-border-color-lighter);
      border-radius: 8px;
    }

    span {
      font-size: 12px;
      color: var(--art-gray-500);
    }

    strong {
      overflow: hidden;
      font-size: 13px;
      font-weight: 600;
      color: var(--art-gray-800);
      text-overflow: ellipsis;
      white-space: nowrap;
    }
  }

  .version-task {
    display: flex;
    flex-direction: column;
    gap: 16px;
  }

  .version-task__version {
    font-size: 16px;
    font-weight: 600;
    color: var(--art-gray-800);
  }

  .version-task__message {
    margin-top: 6px;
    font-size: 13px;
    color: var(--art-gray-500);
  }

  .version-task__logs {
    display: flex;
    flex-direction: column;
    gap: 8px;
    max-height: 280px;
    padding: 12px;
    overflow: auto;
    background: #111827;
    border-radius: 8px;
  }

  .version-task__log {
    display: grid;
    grid-template-columns: 140px minmax(0, 1fr);
    gap: 10px;
    font-size: 12px;
    line-height: 1.6;
    color: #d1d5db;

    span {
      color: #9ca3af;
    }

    strong {
      min-width: 0;
      font-weight: 500;
      word-break: break-word;
    }

    &.is-error strong {
      color: #fca5a5;
    }

    &.is-warn strong {
      color: #fcd34d;
    }
  }

  @media (width <= 1100px) {
    .version-manage-page__grid,
    .version-manage-page__grid--task {
      grid-template-columns: 1fr;
    }

    .version-operation__summary {
      grid-template-columns: 1fr;
    }
  }

  @media (width <= 768px) {
    .version-manage-page__header,
    .version-card__toolbar,
    .version-latest__main,
    .version-task__head {
      flex-direction: column;
      align-items: stretch;
    }

    .version-task__log {
      grid-template-columns: 1fr;
    }
  }
</style>
