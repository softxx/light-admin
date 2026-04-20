<!-- 表格按钮 -->
<template>
  <ElTooltip :content="tooltipText" placement="top" :disabled="!tooltipText">
    <div
      :class="[
        'art-table-button inline-flex items-center justify-center w-8 h-8 mr-2 text-sm c-p rounded-md align-middle shrink-0',
        buttonClass
      ]"
      :style="{ backgroundColor: buttonBgColor, color: iconColor }"
      :title="tooltipText"
      @click="handleClick"
    >
      <ArtSvgIcon :icon="iconContent" />
    </div>
  </ElTooltip>
</template>

<script setup lang="ts">
  defineOptions({ name: 'ArtButtonTable' })

  interface Props {
    /** 按钮类型 */
    type?: 'add' | 'edit' | 'delete' | 'more' | 'view'
    /** 按钮图标 */
    icon?: string
    /** 按钮样式类 */
    tooltip?: string
    iconClass?: string
    /** icon 颜色 */
    iconColor?: string
    /** 按钮背景色 */
    buttonBgColor?: string
  }

  const props = withDefaults(defineProps<Props>(), {})

  const emit = defineEmits<{
    (e: 'click'): void
  }>()

  // 默认按钮配置
  const defaultButtons = {
    add: { icon: 'ri:add-fill', class: 'bg-theme/12 text-theme', tooltip: '新增' },
    edit: { icon: 'ri:pencil-line', class: 'bg-secondary/12 text-secondary', tooltip: '编辑' },
    delete: { icon: 'ri:delete-bin-5-line', class: 'bg-error/12 text-error', tooltip: '删除' },
    view: { icon: 'ri:eye-line', class: 'bg-info/12 text-info', tooltip: '查看' },
    more: { icon: 'ri:more-2-fill', class: '', tooltip: '更多' }
  } as const

  // 获取图标内容
  const iconContent = computed(() => {
    return props.icon || (props.type ? defaultButtons[props.type]?.icon : '') || ''
  })

  // 获取按钮样式类
  const buttonClass = computed(() => {
    return props.iconClass || (props.type ? defaultButtons[props.type]?.class : '') || ''
  })

  const tooltipText = computed(() => {
    return props.tooltip || (props.type ? defaultButtons[props.type]?.tooltip : '') || ''
  })

  const handleClick = () => {
    emit('click')
  }
</script>

<style scoped>
  .art-table-button:last-child {
    margin-right: 0;
  }
</style>
