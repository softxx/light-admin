<template>
  <div class="portal-home">
    <header class="portal-home__header">
      <RouterLink to="/" class="portal-home__brand" aria-label="返回首页">
        <span class="portal-home__logo-wrap">
          <img :src="logoUrl" :alt="systemName" class="portal-home__logo" />
        </span>
        <span>
          <span class="portal-home__eyebrow">Enterprise Portal</span>
          <span class="portal-home__brand-name">{{ systemName }}</span>
        </span>
      </RouterLink>

      <nav class="portal-home__nav" aria-label="门户导航">
        <RouterLink :to="primaryEntryTo">
          {{ userStore.isLogin ? '工作台' : '登录' }}
        </RouterLink>
      </nav>
    </header>

    <main>
      <section class="portal-home__hero">
        <div class="portal-home__hero-copy">
          <div class="portal-home__badge">
            <ArtSvgIcon icon="ri:sparkling-2-line" />
            项目级后台管理中枢
          </div>
          <h1 class="portal-home__title">{{ displayHomepageTitle }}</h1>
          <p class="portal-home__description">{{ displayHomepageIntro }}</p>

          <div class="portal-home__actions">
            <RouterLink
              :to="primaryEntryTo"
              class="portal-home__button portal-home__button--primary"
            >
              <ArtSvgIcon icon="ri:login-circle-line" />
              {{ primaryEntryText }}
            </RouterLink>
          </div>

          <div class="portal-home__stats" aria-label="平台指标">
            <div v-for="item in heroStats" :key="item.label" class="portal-home__stat">
              <strong>{{ item.value }}</strong>
              <span>{{ item.label }}</span>
            </div>
          </div>
        </div>

        <div class="portal-home__preview" aria-label="项目运行总览">
          <div class="portal-home__preview-header">
            <div>
              <span>Operations Console</span>
              <strong>实时运营驾驶舱</strong>
            </div>
            <span class="portal-home__preview-status">在线运行</span>
          </div>

          <div class="portal-home__preview-grid">
            <div
              v-for="item in consoleMetrics"
              :key="item.label"
              class="portal-home__preview-stat"
              :class="`portal-home__preview-stat--${item.tone}`"
            >
              <ArtSvgIcon :icon="item.icon" />
              <div>
                <strong>{{ item.value }}</strong>
                <span>{{ item.label }}</span>
              </div>
            </div>
          </div>

          <div class="portal-home__chart">
            <div class="portal-home__chart-bars">
              <span
                v-for="bar in chartBars"
                :key="bar.month"
                :style="{ height: bar.height }"
                :aria-label="`${bar.month} ${bar.height}`"
              ></span>
            </div>
            <div class="portal-home__chart-footer">
              <span>访问趋势</span>
              <strong>+18.6%</strong>
            </div>
          </div>

          <div class="portal-home__module-list">
            <div v-for="item in quickModules" :key="item.name" class="portal-home__module-item">
              <span>
                <ArtSvgIcon :icon="item.icon" />
                {{ item.name }}
              </span>
              <strong>{{ item.status }}</strong>
            </div>
          </div>
        </div>
      </section>

      <section id="capabilities" class="portal-home__section">
        <div class="portal-home__section-head">
          <span>Core Capabilities</span>
          <h2>覆盖项目管理后台的关键业务场景</h2>
          <p>从数据洞察、权限组织到系统配置和发布管理，首页集中呈现项目当前可交付的主干能力。</p>
        </div>

        <div class="portal-home__capability-grid">
          <article
            v-for="item in capabilityCards"
            :key="item.title"
            class="portal-home__capability-card"
          >
            <div class="portal-home__capability-icon" :class="`is-${item.tone}`">
              <ArtSvgIcon :icon="item.icon" />
            </div>
            <div>
              <h3>{{ item.title }}</h3>
              <p>{{ item.description }}</p>
              <span>{{ item.meta }}</span>
            </div>
          </article>
        </div>
      </section>

      <section id="governance" class="portal-home__governance">
        <div class="portal-home__governance-copy">
          <span>Governance System</span>
          <h2>把后台能力沉淀为稳定、可控、可扩展的治理体系</h2>
          <p>
            平台以权限为边界、以配置为中心、以日志为依据，帮助团队持续维护清晰的系统结构和可靠的运营秩序。
          </p>
        </div>

        <div class="portal-home__governance-list">
          <article v-for="item in governanceItems" :key="item.title">
            <ArtSvgIcon :icon="item.icon" />
            <div>
              <h3>{{ item.title }}</h3>
              <p>{{ item.description }}</p>
            </div>
          </article>
        </div>
      </section>

      <section class="portal-home__cta">
        <div>
          <span>Ready to Work</span>
          <h2>进入项目工作台，开始管理业务与系统资源</h2>
        </div>
        <RouterLink :to="primaryEntryTo" class="portal-home__button portal-home__button--primary">
          <ArtSvgIcon icon="ri:arrow-right-up-line" />
          {{ primaryEntryText }}
        </RouterLink>
      </section>
    </main>
  </div>
</template>

<script setup lang="ts">
  import { RoutesAlias } from '@/router/routesAlias'
  import { useMenuStore } from '@/store/modules/menu'
  import { useSystemConfigStore } from '@/store/modules/system-config'
  import { useUserStore } from '@/store/modules/user'

  defineOptions({ name: 'PortalHome' })

  const DEFAULT_WORKSPACE_ENTRY = RoutesAlias.Layout
  const DEFAULT_HOME_INTRO =
    '面向企业级项目打造的轻量后台门户，整合数据驾驶舱、用户权限、菜单配置、系统设置、日志审计和版本管理等主要功能，让团队以更清晰的视角管理业务、组织和系统资源。'

  const menuStore = useMenuStore()
  const systemConfigStore = useSystemConfigStore()
  const userStore = useUserStore()
  const { systemName, logoUrl } = storeToRefs(systemConfigStore)

  const workspaceEntryTo = computed(() => {
    const homePath = menuStore.getHomePath()
    return homePath || DEFAULT_WORKSPACE_ENTRY
  })

  const primaryEntryTo = computed(() =>
    userStore.isLogin ? workspaceEntryTo.value : RoutesAlias.Login
  )
  const primaryEntryText = computed(() => (userStore.isLogin ? '进入工作台' : '进入后台管理'))
  const displayHomepageTitle = computed(() => systemName.value)
  const displayHomepageIntro = DEFAULT_HOME_INTRO

  const heroStats = [
    { value: '6+', label: '功能模块' },
    { value: 'RBAC', label: '权限体系' },
    { value: '24h', label: '运行观测' }
  ]

  const consoleMetrics = [
    { icon: 'ri:line-chart-line', value: '9,120', label: '总访问量', tone: 'blue' },
    { icon: 'ri:group-line', value: '182', label: '在线访客', tone: 'green' },
    { icon: 'ri:fire-line', value: '95.2%', label: '任务完成率', tone: 'gold' }
  ]

  const chartBars = [
    { month: 'Jan', height: '46%' },
    { month: 'Feb', height: '62%' },
    { month: 'Mar', height: '40%' },
    { month: 'Apr', height: '72%' },
    { month: 'May', height: '58%' },
    { month: 'Jun', height: '84%' },
    { month: 'Jul', height: '66%' },
    { month: 'Aug', height: '76%' }
  ]

  const quickModules = [
    { icon: 'ri:dashboard-3-line', name: '数据驾驶舱', status: '已接入' },
    { icon: 'ri:shield-user-line', name: '权限角色', status: '可配置' },
    { icon: 'ri:file-list-3-line', name: '操作日志', status: '可追踪' }
  ]

  const capabilityCards = [
    {
      icon: 'ri:dashboard-3-line',
      title: '数据驾驶舱',
      description: '聚合访问趋势、活跃用户、新增用户、动态统计和待办事项，快速掌握项目运行状态。',
      meta: '趋势图表 / 指标卡片 / 运营待办',
      tone: 'blue'
    },
    {
      icon: 'ri:user-settings-line',
      title: '用户与组织',
      description: '支持用户资料、部门结构、个人中心等管理场景，适配后台日常运营维护。',
      meta: '用户档案 / 部门管理 / 个人中心',
      tone: 'green'
    },
    {
      icon: 'ri:shield-keyhole-line',
      title: '角色权限',
      description: '基于角色控制菜单和操作权限，让不同身份在清晰边界内高效协作。',
      meta: '角色授权 / 权限标识 / 访问控制',
      tone: 'gold'
    },
    {
      icon: 'ri:menu-search-line',
      title: '菜单配置',
      description: '沉淀路由、菜单、图标、缓存和隐藏规则，支撑系统模块持续扩展。',
      meta: '动态菜单 / 路由管理 / 页面缓存',
      tone: 'rose'
    },
    {
      icon: 'ri:settings-3-line',
      title: '系统设置',
      description: '统一维护系统名称、Logo、Favicon 和首页开关，降低品牌信息维护成本。',
      meta: '品牌配置 / 首页开关 / 实时预览',
      tone: 'blue'
    },
    {
      icon: 'ri:git-branch-line',
      title: '版本与日志',
      description: '结合版本管理、登录日志、操作日志和异常页面，提高交付可追踪性。',
      meta: '版本记录 / 登录日志 / 异常兜底',
      tone: 'green'
    }
  ]

  const governanceItems = [
    {
      icon: 'ri:lock-2-line',
      title: '安全边界清晰',
      description: '角色、菜单、按钮权限分层控制，关键操作有迹可循。'
    },
    {
      icon: 'ri:equalizer-line',
      title: '配置集中维护',
      description: '系统品牌、菜单结构、字典缓存等配置集中沉淀，减少重复修改。'
    },
    {
      icon: 'ri:rocket-2-line',
      title: '交付持续演进',
      description: '版本管理与结果反馈页面让发布、异常和业务流转更容易被团队感知。'
    }
  ]
</script>

<style scoped lang="scss">
  .portal-home {
    position: relative;
    min-height: 100vh;
    padding: 28px;
    overflow: hidden;
    color: #17181d;
    background:
      linear-gradient(180deg, #fff 0%, #f8fbff 52%, #fff 100%),
      repeating-linear-gradient(
        90deg,
        rgb(15 23 42 / 3%) 0,
        rgb(15 23 42 / 3%) 1px,
        transparent 1px,
        transparent 80px
      );
  }

  .portal-home::before {
    position: absolute;
    inset: 0;
    pointer-events: none;
    content: '';
    background:
      linear-gradient(180deg, rgb(255 255 255 / 90%), transparent 42%),
      linear-gradient(90deg, transparent, rgb(37 99 235 / 6%) 50%, transparent);
  }

  .portal-home__header,
  .portal-home__hero,
  .portal-home__section,
  .portal-home__governance,
  .portal-home__cta {
    position: relative;
    z-index: 1;
    width: min(1200px, 100%);
    margin: 0 auto;
  }

  .portal-home__header {
    display: flex;
    gap: 24px;
    align-items: center;
    justify-content: space-between;
    height: 72px;
    padding: 0 18px;
    background: rgb(255 255 255 / 92%);
    backdrop-filter: blur(18px);
    border: 1px solid rgb(15 23 42 / 8%);
    border-radius: 8px;
    box-shadow: 0 18px 48px rgb(15 23 42 / 8%);
  }

  .portal-home__brand {
    display: inline-flex;
    gap: 12px;
    align-items: center;
    min-width: 0;
    color: inherit;
    text-decoration: none;
  }

  .portal-home__logo-wrap {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 48px;
    height: 48px;
    background: linear-gradient(180deg, #fff, #f7faff);
    border: 1px solid rgb(15 23 42 / 8%);
    border-radius: 8px;
    box-shadow: inset 0 1px 0 rgb(255 255 255 / 82%);
  }

  .portal-home__logo {
    width: 34px;
    height: 34px;
    object-fit: contain;
  }

  .portal-home__eyebrow,
  .portal-home__brand-name {
    display: block;
  }

  .portal-home__eyebrow,
  .portal-home__section-head span,
  .portal-home__governance-copy span,
  .portal-home__cta span {
    font-size: 12px;
    font-weight: 700;
    line-height: 1.2;
    color: #1d4ed8;
    text-transform: uppercase;
    letter-spacing: 0;
  }

  .portal-home__brand-name {
    max-width: 240px;
    margin-top: 4px;
    overflow: hidden;
    font-size: 18px;
    font-weight: 700;
    line-height: 1.2;
    color: #17181d;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  .portal-home__nav {
    display: flex;
    gap: 6px;
    align-items: center;
  }

  .portal-home__nav a {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    height: 38px;
    padding: 0 14px;
    font-size: 14px;
    font-weight: 600;
    color: #4b5563;
    text-decoration: none;
    border-radius: 8px;
    transition:
      color 0.2s ease,
      background 0.2s ease;
  }

  .portal-home__nav a:hover {
    color: #17181d;
    background: rgb(37 99 235 / 7%);
  }

  .portal-home__hero {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(420px, 520px);
    gap: 38px;
    align-items: center;
    min-height: calc(100vh - 132px);
    padding: 74px 0 54px;
  }

  .portal-home__hero-copy {
    min-width: 0;
  }

  .portal-home__badge,
  .portal-home__button,
  .portal-home__stat,
  .portal-home__preview-status,
  .portal-home__module-item,
  .portal-home__capability-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
  }

  .portal-home__badge {
    gap: 8px;
    height: 36px;
    padding: 0 12px;
    font-size: 14px;
    font-weight: 700;
    color: #1d4ed8;
    background: rgb(239 246 255 / 88%);
    border: 1px solid rgb(37 99 235 / 16%);
    border-radius: 8px;
  }

  .portal-home__title {
    max-width: 760px;
    margin: 26px 0 20px;
    font-size: 58px;
    font-weight: 800;
    line-height: 1.06;
    color: #111217;
  }

  .portal-home__description {
    max-width: 680px;
    margin: 0;
    font-size: 17px;
    line-height: 1.9;
    color: #555e6d;
    white-space: pre-line;
  }

  .portal-home__actions {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-top: 32px;
  }

  .portal-home__button {
    gap: 8px;
    min-width: 148px;
    height: 46px;
    padding: 0 18px;
    font-size: 15px;
    font-weight: 700;
    text-decoration: none;
    border: 1px solid transparent;
    border-radius: 8px;
    transition:
      transform 0.2s ease,
      box-shadow 0.2s ease,
      border-color 0.2s ease,
      background 0.2s ease;
  }

  .portal-home__button:hover {
    transform: translateY(-2px);
  }

  .portal-home__button--primary {
    color: #fff;
    background: linear-gradient(135deg, #17181d, #2e3340);
    box-shadow: 0 16px 34px rgb(23 24 29 / 22%);
  }

  .portal-home__button--ghost {
    color: #17181d;
    background: rgb(255 255 255 / 78%);
    border-color: rgb(23 24 29 / 10%);
  }

  .portal-home__button--ghost:hover {
    background: #fff;
    border-color: rgb(23 24 29 / 18%);
    box-shadow: 0 14px 28px rgb(23 24 29 / 8%);
  }

  .portal-home__stats {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 150px));
    gap: 12px;
    margin-top: 42px;
  }

  .portal-home__stat {
    flex-direction: column;
    align-items: flex-start;
    min-height: 82px;
    padding: 14px 16px;
    background: #fff;
    border: 1px solid rgb(15 23 42 / 8%);
    border-radius: 8px;
    box-shadow: 0 16px 34px rgb(15 23 42 / 5%);
  }

  .portal-home__stat strong {
    font-size: 24px;
    line-height: 1;
    color: #17181d;
  }

  .portal-home__stat span {
    margin-top: 10px;
    font-size: 13px;
    color: #647085;
  }

  .portal-home__preview {
    position: relative;
    padding: 20px;
    overflow: hidden;
    color: #172033;
    background:
      linear-gradient(180deg, #fff 0%, #f8fbff 100%),
      linear-gradient(90deg, rgb(37 99 235 / 6%), rgb(15 159 110 / 5%));
    border: 1px solid rgb(15 23 42 / 10%);
    border-radius: 8px;
    box-shadow:
      0 30px 80px rgb(15 23 42 / 12%),
      inset 0 1px 0 rgb(255 255 255 / 88%);
  }

  .portal-home__preview::after {
    position: absolute;
    inset: 0;
    pointer-events: none;
    content: '';
    background:
      linear-gradient(90deg, rgb(37 99 235 / 6%) 1px, transparent 1px),
      linear-gradient(0deg, rgb(15 23 42 / 4%) 1px, transparent 1px);
    background-size: 44px 44px;
    mask-image: linear-gradient(180deg, #000, transparent 76%);
  }

  .portal-home__preview > * {
    position: relative;
    z-index: 1;
  }

  .portal-home__preview-header {
    display: flex;
    gap: 16px;
    align-items: flex-start;
    justify-content: space-between;
  }

  .portal-home__preview-header span {
    display: block;
    font-size: 13px;
    color: #64748b;
  }

  .portal-home__preview-header strong {
    display: block;
    margin-top: 8px;
    font-size: 22px;
  }

  .portal-home__preview-status {
    width: 84px;
    height: 30px;
    font-size: 12px;
    font-weight: 700;
    color: #047857;
    background: rgb(236 253 245 / 92%);
    border: 1px solid rgb(16 185 129 / 20%);
    border-radius: 8px;
  }

  .portal-home__preview-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 10px;
    margin-top: 24px;
  }

  .portal-home__preview-stat {
    display: flex;
    gap: 10px;
    align-items: center;
    min-height: 82px;
    padding: 14px;
    background: #fff;
    border: 1px solid rgb(15 23 42 / 8%);
    border-radius: 8px;
    box-shadow: 0 14px 28px rgb(15 23 42 / 5%);
  }

  .portal-home__preview-stat > .svg-icon {
    flex: none;
    font-size: 22px;
  }

  .portal-home__preview-stat--blue > .svg-icon {
    color: #8eb0ff;
  }

  .portal-home__preview-stat--green > .svg-icon {
    color: #8ff1c3;
  }

  .portal-home__preview-stat--gold > .svg-icon {
    color: #f6cc7a;
  }

  .portal-home__preview-stat strong,
  .portal-home__preview-stat span {
    display: block;
  }

  .portal-home__preview-stat strong {
    font-size: 18px;
    line-height: 1;
  }

  .portal-home__preview-stat span {
    margin-top: 8px;
    font-size: 12px;
    color: #64748b;
  }

  .portal-home__chart {
    padding: 18px;
    margin-top: 16px;
    background: #f8fafc;
    border: 1px solid rgb(15 23 42 / 8%);
    border-radius: 8px;
  }

  .portal-home__chart-bars {
    display: flex;
    gap: 10px;
    align-items: end;
    height: 150px;
  }

  .portal-home__chart-bars span {
    flex: 1;
    min-width: 0;
    background: linear-gradient(180deg, #8eb0ff, #2156f3);
    border-radius: 8px 8px 0 0;
  }

  .portal-home__chart-bars span:nth-child(2n) {
    background: linear-gradient(180deg, #8ff1c3, #0f9f6e);
  }

  .portal-home__chart-bars span:nth-child(3n) {
    background: linear-gradient(180deg, #f6cc7a, #b87916);
  }

  .portal-home__chart-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: 16px;
    font-size: 13px;
    color: #64748b;
  }

  .portal-home__chart-footer strong {
    color: #059669;
  }

  .portal-home__module-list {
    display: grid;
    gap: 10px;
    margin-top: 16px;
  }

  .portal-home__module-item {
    gap: 16px;
    justify-content: space-between;
    min-height: 48px;
    padding: 0 14px;
    background: #fff;
    border: 1px solid rgb(15 23 42 / 8%);
    border-radius: 8px;
  }

  .portal-home__module-item span {
    display: inline-flex;
    gap: 8px;
    align-items: center;
    min-width: 0;
    font-size: 14px;
    color: #334155;
  }

  .portal-home__module-item strong {
    flex: none;
    font-size: 13px;
    color: #1d4ed8;
  }

  .portal-home__section,
  .portal-home__governance,
  .portal-home__cta {
    padding: 68px 0;
  }

  .portal-home__section-head {
    max-width: 760px;
  }

  .portal-home__section-head h2,
  .portal-home__governance-copy h2,
  .portal-home__cta h2 {
    margin: 12px 0 0;
    font-size: 36px;
    font-weight: 800;
    line-height: 1.18;
    color: #17181d;
  }

  .portal-home__section-head p,
  .portal-home__governance-copy p {
    margin: 16px 0 0;
    font-size: 16px;
    line-height: 1.8;
    color: #5b6472;
  }

  .portal-home__capability-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 16px;
    margin-top: 28px;
  }

  .portal-home__capability-card {
    display: grid;
    grid-template-columns: 48px minmax(0, 1fr);
    gap: 16px;
    min-height: 210px;
    padding: 22px;
    background: #fff;
    border: 1px solid rgb(15 23 42 / 8%);
    border-radius: 8px;
    box-shadow: 0 18px 44px rgb(15 23 42 / 6%);
    transition:
      transform 0.2s ease,
      box-shadow 0.2s ease,
      border-color 0.2s ease;
  }

  .portal-home__capability-card:hover {
    border-color: rgb(37 99 235 / 18%);
    box-shadow: 0 24px 54px rgb(15 23 42 / 9%);
    transform: translateY(-3px);
  }

  .portal-home__capability-icon {
    width: 48px;
    height: 48px;
    font-size: 22px;
    border-radius: 8px;
  }

  .portal-home__capability-icon.is-blue {
    color: #2156f3;
    background: rgb(33 86 243 / 10%);
  }

  .portal-home__capability-icon.is-green {
    color: #0f9f6e;
    background: rgb(15 159 110 / 12%);
  }

  .portal-home__capability-icon.is-gold {
    color: #d97706;
    background: rgb(245 158 11 / 14%);
  }

  .portal-home__capability-icon.is-rose {
    color: #d95b6a;
    background: rgb(217 91 106 / 12%);
  }

  .portal-home__capability-card h3,
  .portal-home__governance-list h3 {
    margin: 0;
    font-size: 18px;
    font-weight: 800;
    line-height: 1.35;
    color: #17181d;
  }

  .portal-home__capability-card p,
  .portal-home__governance-list p {
    margin: 12px 0 0;
    font-size: 14px;
    line-height: 1.75;
    color: #606a79;
  }

  .portal-home__capability-card span {
    display: block;
    margin-top: 18px;
    font-size: 13px;
    font-weight: 700;
    color: #1d4ed8;
  }

  .portal-home__governance {
    display: grid;
    grid-template-columns: minmax(0, 0.85fr) minmax(360px, 1fr);
    gap: 44px;
    align-items: center;
  }

  .portal-home__governance-list {
    display: grid;
    gap: 14px;
  }

  .portal-home__governance-list article {
    display: grid;
    grid-template-columns: 46px minmax(0, 1fr);
    gap: 16px;
    padding: 20px;
    background: #fff;
    border: 1px solid rgb(15 23 42 / 8%);
    border-radius: 8px;
    box-shadow: 0 16px 34px rgb(15 23 42 / 5%);
  }

  .portal-home__governance-list .svg-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 46px;
    height: 46px;
    font-size: 22px;
    color: #2156f3;
    background: rgb(33 86 243 / 10%);
    border-radius: 8px;
  }

  .portal-home__governance-list article:nth-child(2) .svg-icon {
    color: #0f9f6e;
    background: rgb(15 159 110 / 12%);
  }

  .portal-home__governance-list article:nth-child(3) .svg-icon {
    color: #d95b6a;
    background: rgb(217 91 106 / 12%);
  }

  .portal-home__cta {
    display: flex;
    gap: 28px;
    align-items: center;
    justify-content: space-between;
    padding: 34px;
    margin-bottom: 28px;
    background:
      linear-gradient(180deg, #fff, #f8fbff),
      linear-gradient(90deg, rgb(37 99 235 / 6%), rgb(15 159 110 / 5%));
    border: 1px solid rgb(15 23 42 / 8%);
    border-radius: 8px;
    box-shadow: 0 22px 54px rgb(15 23 42 / 7%);
  }

  .portal-home__cta h2 {
    max-width: 760px;
    font-size: 28px;
  }

  @media (width <= 1080px) {
    .portal-home__hero,
    .portal-home__governance {
      grid-template-columns: 1fr;
    }

    .portal-home__hero {
      min-height: auto;
      padding-top: 56px;
    }

    .portal-home__preview {
      max-width: 680px;
    }

    .portal-home__capability-grid {
      grid-template-columns: repeat(2, minmax(0, 1fr));
    }
  }

  @media (width <= 760px) {
    .portal-home {
      padding: 14px;
    }

    .portal-home__header {
      flex-direction: column;
      align-items: stretch;
      height: auto;
      padding: 12px;
    }

    .portal-home__brand-name {
      max-width: 210px;
    }

    .portal-home__nav {
      justify-content: space-between;
      width: 100%;
      padding-top: 10px;
      border-top: 1px solid rgb(15 23 42 / 8%);
    }

    .portal-home__nav a {
      flex: 1;
      padding: 0 8px;
      font-size: 13px;
    }

    .portal-home__hero {
      gap: 28px;
      padding: 42px 0;
    }

    .portal-home__title {
      margin-top: 22px;
      font-size: 40px;
      line-height: 1.12;
    }

    .portal-home__description {
      font-size: 15px;
      line-height: 1.85;
    }

    .portal-home__button {
      width: 100%;
    }

    .portal-home__stats,
    .portal-home__preview-grid,
    .portal-home__capability-grid {
      grid-template-columns: 1fr;
    }

    .portal-home__preview {
      padding: 16px;
    }

    .portal-home__preview-header {
      flex-direction: column;
    }

    .portal-home__chart-bars {
      height: 118px;
    }

    .portal-home__section,
    .portal-home__governance {
      padding: 44px 0;
    }

    .portal-home__section-head h2,
    .portal-home__governance-copy h2 {
      font-size: 30px;
    }

    .portal-home__capability-card,
    .portal-home__governance-list article {
      grid-template-columns: 1fr;
    }

    .portal-home__cta {
      flex-direction: column;
      align-items: stretch;
      padding: 22px;
    }

    .portal-home__cta h2 {
      font-size: 24px;
    }
  }
</style>
