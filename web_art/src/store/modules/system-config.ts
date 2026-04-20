import AppConfig from '@/config'
import defaultLogo from '@imgs/common/logo.webp'
import defaultFavicon from '@imgs/favicon.ico'
import { fetchGetPublicSystemSetting } from '@/api/system-manage'

type SystemSettingPayload = Partial<Api.SystemManage.SystemSettingItem>

const DEFAULT_SYSTEM_SETTING: Api.SystemManage.SystemSettingItem = {
  system_name: AppConfig.systemInfo.name,
  logo: '',
  favicon: '',
  homepage_enabled: 1,
  homepage_title: '项目管理平台',
  homepage_intro:
    '这里先作为项目的简洁首页，展示项目介绍与后台入口。后续可以在系统设置中继续调整文案，不需要每次修改源码。'
}

function ensureFaviconLink(): HTMLLinkElement {
  const existingLink =
    document.querySelector('link[rel="shortcut icon"]') ||
    document.querySelector('link[rel="icon"]')

  if (existingLink instanceof HTMLLinkElement) {
    return existingLink
  }

  const link = document.createElement('link')
  link.rel = 'shortcut icon'
  document.head.appendChild(link)
  return link
}

function replaceSystemNameInTitle(currentTitle: string, nextSystemName: string): string {
  const title = currentTitle.trim()

  if (!title) {
    return nextSystemName
  }

  if (title === DEFAULT_SYSTEM_SETTING.system_name) {
    return nextSystemName
  }

  if (title.includes(' - ')) {
    const titleParts = title.split(' - ')
    titleParts[titleParts.length - 1] = nextSystemName
    return titleParts.join(' - ')
  }

  return title
}

export const useSystemConfigStore = defineStore(
  'systemConfigStore',
  () => {
    const systemSetting = ref<Api.SystemManage.SystemSettingItem>({ ...DEFAULT_SYSTEM_SETTING })
    const loaded = ref(false)

    const systemName = computed(
      () => systemSetting.value.system_name || DEFAULT_SYSTEM_SETTING.system_name
    )
    const logoUrl = computed(() => systemSetting.value.logo || defaultLogo)
    const faviconUrl = computed(() => systemSetting.value.favicon || defaultFavicon)
    const homepageEnabled = computed(
      () => Number(systemSetting.value.homepage_enabled ?? DEFAULT_SYSTEM_SETTING.homepage_enabled) !== 0
    )
    const homepageTitle = computed(
      () =>
        systemSetting.value.homepage_title ||
        DEFAULT_SYSTEM_SETTING.homepage_title ||
        systemName.value
    )
    const homepageIntro = computed(
      () =>
        systemSetting.value.homepage_intro ||
        DEFAULT_SYSTEM_SETTING.homepage_intro ||
        '这里先作为项目的简洁首页，展示项目介绍与后台入口。'
    )

    const applyFavicon = () => {
      const faviconLink = ensureFaviconLink()
      faviconLink.type = faviconUrl.value.endsWith('.ico') ? 'image/x-icon' : 'image/png'
      faviconLink.href = faviconUrl.value
    }

    const applyDocumentTitle = () => {
      document.title = replaceSystemNameInTitle(document.title, systemName.value)
    }

    const normalizeSetting = (
      payload?: SystemSettingPayload | null
    ): Api.SystemManage.SystemSettingItem => ({
      system_name: payload?.system_name?.trim() || DEFAULT_SYSTEM_SETTING.system_name,
      logo: payload?.logo?.trim() || '',
      favicon: payload?.favicon?.trim() || '',
      homepage_enabled:
        Number(payload?.homepage_enabled ?? DEFAULT_SYSTEM_SETTING.homepage_enabled) === 0 ? 0 : 1,
      homepage_title: payload?.homepage_title?.trim() || DEFAULT_SYSTEM_SETTING.homepage_title,
      homepage_intro: payload?.homepage_intro?.trim() || DEFAULT_SYSTEM_SETTING.homepage_intro
    })

    const setSystemSetting = (
      payload?: SystemSettingPayload | null,
      options: { markLoaded?: boolean } = {}
    ) => {
      systemSetting.value = normalizeSetting(payload)
      if (options.markLoaded !== false) {
        loaded.value = true
      }
      applyDocumentTitle()
      applyFavicon()
    }

    const syncDocumentBranding = () => {
      systemSetting.value = normalizeSetting(systemSetting.value)
      applyDocumentTitle()
      applyFavicon()
    }

    const loadPublicSystemSetting = async (force = false) => {
      if (loaded.value && !force) {
        syncDocumentBranding()
        return systemSetting.value
      }

      try {
        const data = await fetchGetPublicSystemSetting()
        setSystemSetting(data)
        return systemSetting.value
      } catch {
        setSystemSetting(undefined, { markLoaded: false })
        return systemSetting.value
      }
    }

    return {
      loaded,
      systemSetting,
      systemName,
      logoUrl,
      faviconUrl,
      homepageEnabled,
      homepageTitle,
      homepageIntro,
      syncDocumentBranding,
      setSystemSetting,
      loadPublicSystemSetting
    }
  },
  {
    persist: true
  }
)
