<template>
  <ElDialog v-model="visible" title="代码预览" width="900px" align-center>
    <ElCollapse>
      <ElCollapseItem
        v-for="item in normalizedPreviewList"
        :key="item.name"
        :title="item.name"
        :name="item.name"
      >
        <pre class="preview-code">{{ item.content }}</pre>
      </ElCollapseItem>
    </ElCollapse>
  </ElDialog>
</template>

<script setup lang="ts">
  interface Props {
    modelValue: boolean
    previewData?: Record<string, any> | any[]
  }

  const props = withDefaults(defineProps<Props>(), {
    modelValue: false,
    previewData: () => ({})
  })

  const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void
  }>()

  const visible = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value)
  })

  const normalizedPreviewList = computed(() => {
    if (Array.isArray(props.previewData)) {
      return props.previewData.map((item, index) => ({
        name: item?.name || `文件 ${index + 1}`,
        content: item?.content || JSON.stringify(item, null, 2)
      }))
    }

    return Object.entries(props.previewData || {}).map(([name, content]) => ({
      name,
      content: typeof content === 'string' ? content : JSON.stringify(content, null, 2)
    }))
  })
</script>

<style scoped>
  .preview-code {
    margin: 0;
    padding: 16px;
    overflow: auto;
    font-size: 12px;
    line-height: 1.6;
    white-space: pre-wrap;
    word-break: break-all;
    border-radius: 10px;
    background: var(--art-main-bg-color);
  }
</style>
