<!-- 系统logo -->
<template>
  <div class="flex-cc">
    <img :style="logoStyle" :src="logoUrl" :alt="systemName" class="w-full h-full" />
  </div>
</template>

<script setup lang="ts">
  import { useSystemConfigStore } from '@/store/modules/system-config'

  defineOptions({ name: 'ArtLogo' })

  interface Props {
    /** logo 大小 */
    size?: number | string
  }

  const props = withDefaults(defineProps<Props>(), {
    size: 36
  })

  const { logoUrl, systemName } = storeToRefs(useSystemConfigStore())
  const logoStyle = computed(() => ({ width: `${props.size}px` }))
</script>
