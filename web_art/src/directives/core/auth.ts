import type { App, Directive, DirectiveBinding } from 'vue'
import { router } from '@/router'
import { useUserStore } from '@/store/modules/user'

export type AuthDirective = Directive<HTMLElement, string>

function removeElement(el: HTMLElement) {
  if (el.parentNode) {
    el.parentNode.removeChild(el)
  }
}

function hasButtonPermission(auth: string) {
  const userStore = useUserStore()

  if (userStore.info?.is_super_admin) {
    return true
  }

  const buttons = Array.isArray(userStore.info?.buttons)
    ? userStore.info.buttons
    : Array.isArray(userStore.info?.rules)
      ? userStore.info.rules
      : []

  if (buttons.includes(auth)) {
    return true
  }

  const authList = (router.currentRoute.value.meta.authList as Array<{ authMark: string }>) || []
  return authList.some((item) => item.authMark === auth)
}

function checkAuthPermission(el: HTMLElement, binding: DirectiveBinding<string>) {
  if (!binding.value || hasButtonPermission(binding.value)) {
    return
  }

  removeElement(el)
}

const authDirective: AuthDirective = {
  mounted: checkAuthPermission,
  updated: checkAuthPermission
}

export function setupAuthDirective(app: App) {
  app.directive('auth', authDirective)
}
