<template>
  <div class="portal-home">
    <div class="portal-home__glow portal-home__glow--left"></div>
    <div class="portal-home__glow portal-home__glow--right"></div>

    <div class="portal-home__shell">
      <header class="portal-home__header">
        <div class="portal-home__brand">
          <img :src="logoUrl" :alt="systemName" class="portal-home__logo" />
          <div>
            <div class="portal-home__eyebrow">Project Portal</div>
            <div class="portal-home__brand-name">{{ systemName }}</div>
          </div>
        </div>

        <RouterLink :to="primaryEntryTo" class="portal-home__header-link">
          {{ primaryEntryText }}
        </RouterLink>
      </header>

      <main class="portal-home__content">
        <section class="portal-home__hero">
          <div class="portal-home__tag">首页介绍</div>
          <h1 class="portal-home__title">{{ homepageTitle }}</h1>
          <p class="portal-home__description">{{ homepageIntro }}</p>

          <div class="portal-home__actions">
            <RouterLink :to="primaryEntryTo" class="portal-home__button portal-home__button--primary">
              {{ primaryEntryText }}
            </RouterLink>
          </div>
        </section>

        <section class="portal-home__cards">
          <article v-for="item in featureCards" :key="item.title" class="portal-home__card">
            <div class="portal-home__card-title">{{ item.title }}</div>
            <p class="portal-home__card-text">{{ item.description }}</p>
          </article>
        </section>
      </main>
    </div>
  </div>
</template>

<script setup lang="ts">
  import { RoutesAlias } from '@/router/routesAlias'
  import { useMenuStore } from '@/store/modules/menu'
  import { useSystemConfigStore } from '@/store/modules/system-config'
  import { useUserStore } from '@/store/modules/user'

  defineOptions({ name: 'PortalHome' })

  const DEFAULT_WORKSPACE_ENTRY = RoutesAlias.Layout

  const menuStore = useMenuStore()
  const systemConfigStore = useSystemConfigStore()
  const userStore = useUserStore()
  const { systemName, logoUrl, homepageTitle, homepageIntro } = storeToRefs(systemConfigStore)

  const workspaceEntryTo = computed(() => {
    const homePath = menuStore.getHomePath()
    return homePath || DEFAULT_WORKSPACE_ENTRY
  })

  const primaryEntryTo = computed(() =>
    userStore.isLogin ? workspaceEntryTo.value : RoutesAlias.Login
  )
  const primaryEntryText = computed(() => (userStore.isLogin ? '进入工作台' : '进入后台管理'))

  const featureCards = [
    {
      title: '项目简介',
      description: '首页先保持简洁，主要用于放项目介绍和后台入口，后续可以再逐步扩展内容。'
    },
    {
      title: '后台入口',
      description: '未登录时会进入标准登录页，登录后也可以继续访问首页，再按需要进入工作台。'
    },
    {
      title: '后续扩展',
      description: '等你后面想清楚首页方向后，这里可以继续补充产品亮点、案例和联系方式。'
    }
  ]
</script>

<style scoped lang="scss">
  .portal-home {
    position: relative;
    min-height: 100vh;
    padding: 32px 20px;
    overflow: hidden;
    background:
      linear-gradient(180deg, #f7f8fc 0%, #eef3ff 52%, #f7f7fb 100%),
      radial-gradient(circle at top left, rgb(93 135 255 / 14%), transparent 28%);
  }

  .portal-home__glow {
    position: absolute;
    z-index: 0;
    width: 380px;
    height: 380px;
    border-radius: 999px;
    filter: blur(70px);
    opacity: 0.55;
  }

  .portal-home__glow--left {
    top: -120px;
    left: -120px;
    background: rgb(93 135 255 / 24%);
  }

  .portal-home__glow--right {
    right: -150px;
    bottom: -140px;
    background: rgb(56 192 252 / 18%);
  }

  .portal-home__shell {
    position: relative;
    z-index: 1;
    width: min(1120px, 100%);
    margin: 0 auto;
    padding: 28px;
    border: 1px solid rgb(255 255 255 / 65%);
    border-radius: 28px;
    background: rgb(255 255 255 / 72%);
    box-shadow:
      0 28px 80px rgb(22 37 84 / 10%),
      inset 0 1px 0 rgb(255 255 255 / 78%);
    backdrop-filter: blur(18px);
  }

  .portal-home__header,
  .portal-home__brand,
  .portal-home__actions {
    display: flex;
    align-items: center;
  }

  .portal-home__header {
    justify-content: space-between;
    gap: 18px;
    margin-bottom: 42px;
  }

  .portal-home__brand {
    gap: 14px;
  }

  .portal-home__logo {
    width: 58px;
    height: 58px;
    padding: 8px;
    border: 1px solid rgb(148 163 184 / 18%);
    border-radius: 18px;
    background: linear-gradient(180deg, rgb(255 255 255 / 96%), rgb(244 247 255 / 94%));
    object-fit: cover;
    box-shadow: 0 12px 24px rgb(15 23 42 / 8%);
  }

  .portal-home__eyebrow {
    font-size: 12px;
    font-weight: 600;
    letter-spacing: 0.12em;
    color: var(--el-color-primary);
    text-transform: uppercase;
  }

  .portal-home__brand-name {
    margin-top: 4px;
    font-size: 18px;
    font-weight: 600;
    color: var(--art-gray-800);
  }

  .portal-home__header-link,
  .portal-home__button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 999px;
    text-decoration: none;
    transition:
      transform 0.2s ease,
      box-shadow 0.2s ease,
      border-color 0.2s ease,
      background 0.2s ease;
  }

  .portal-home__header-link {
    min-width: 138px;
    height: 44px;
    padding: 0 18px;
    border: 1px solid rgb(93 135 255 / 18%);
    color: var(--el-color-primary);
    background: rgb(255 255 255 / 72%);
  }

  .portal-home__header-link:hover,
  .portal-home__button:hover {
    transform: translateY(-1px);
  }

  .portal-home__content {
    display: grid;
    gap: 24px;
    grid-template-columns: minmax(0, 1.3fr) minmax(280px, 0.9fr);
  }

  .portal-home__hero,
  .portal-home__card {
    padding: 28px;
    border: 1px solid rgb(148 163 184 / 16%);
    border-radius: 24px;
    background: rgb(255 255 255 / 74%);
    box-shadow: 0 18px 40px rgb(15 23 42 / 6%);
  }

  .portal-home__tag {
    display: inline-flex;
    align-items: center;
    height: 32px;
    padding: 0 14px;
    border-radius: 999px;
    color: var(--el-color-primary);
    background: rgb(93 135 255 / 10%);
    font-size: 13px;
    font-weight: 500;
  }

  .portal-home__title {
    margin: 20px 0 16px;
    font-size: clamp(32px, 4vw, 48px);
    line-height: 1.12;
    color: #18233d;
  }

  .portal-home__description {
    max-width: 720px;
    margin: 0;
    font-size: 16px;
    line-height: 1.9;
    color: var(--art-gray-600);
    white-space: pre-line;
  }

  .portal-home__actions {
    flex-wrap: wrap;
    gap: 14px;
    margin-top: 28px;
  }

  .portal-home__button {
    min-width: 160px;
    height: 48px;
    padding: 0 22px;
    font-size: 15px;
    font-weight: 500;
  }

  .portal-home__button--primary {
    color: #fff;
    background: linear-gradient(135deg, #5d87ff, #3e63dd);
    box-shadow: 0 16px 28px rgb(93 135 255 / 24%);
  }

  .portal-home__cards {
    display: grid;
    gap: 18px;
  }

  .portal-home__card-title {
    margin-bottom: 12px;
    font-size: 18px;
    font-weight: 600;
    color: #18233d;
  }

  .portal-home__card-text {
    margin: 0;
    font-size: 14px;
    line-height: 1.8;
    color: var(--art-gray-600);
  }

  @media (width <= 900px) {
    .portal-home {
      padding: 18px;
    }

    .portal-home__shell {
      padding: 22px;
      border-radius: 22px;
    }

    .portal-home__header {
      flex-direction: column;
      align-items: flex-start;
      margin-bottom: 30px;
    }

    .portal-home__content {
      grid-template-columns: 1fr;
    }

    .portal-home__hero,
    .portal-home__card {
      padding: 22px;
    }
  }
</style>
