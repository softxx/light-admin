import App from './App.vue'
import { createApp } from 'vue'
import { initStore, store } from './store'
import { initRouter } from './router'
import language from './locales'
import '@styles/core/tailwind.css'
import '@styles/index.scss'
import { setupGlobDirectives } from './directives'
import { setupErrorHandle } from './utils/sys/error-handle'
import { useSystemConfigStore } from './store/modules/system-config'
import "@utils/ui/iconify-loader"; // 离线图标加载

document.addEventListener(
  'touchstart',
  function () {},
  { passive: false }
)

async function bootstrap() {
  const app = createApp(App)
  initStore(app)

  const systemConfigStore = useSystemConfigStore(store)
  systemConfigStore.syncDocumentBranding()
  await systemConfigStore.loadPublicSystemSetting()

  initRouter(app)
  setupGlobDirectives(app)
  setupErrorHandle(app)

  app.use(language)
  app.mount('#app')
}

bootstrap()
