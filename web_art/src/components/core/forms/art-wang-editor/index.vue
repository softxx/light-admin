<template>
  <div class="editor-wrapper">
    <Toolbar
      class="editor-toolbar"
      :editor="editorRef"
      :mode="mode"
      :defaultConfig="toolbarConfig"
    />
    <Editor
      v-model="modelValue"
      :style="{ height, overflowY: 'hidden' }"
      :mode="mode"
      :defaultConfig="editorConfig"
      @onCreated="onCreateEditor"
    />
  </div>
</template>

<script setup lang="ts">
  import '@wangeditor/editor/dist/css/style.css'
  import { computed, onBeforeUnmount, shallowRef } from 'vue'
  import { Editor, Toolbar } from '@wangeditor/editor-for-vue'
  import type { IDomEditor, IEditorConfig, IToolbarConfig } from '@wangeditor/editor'
  import EmojiText from '@/utils/ui/emojo'
  import request from '@/utils/http'

  defineOptions({ name: 'ArtWangEditor' })

  type InsertFnType = (url: string, alt: string, href: string) => void

  const { VITE_API_URL } = import.meta.env

  interface Props {
    height?: string
    toolbarKeys?: string[]
    insertKeys?: { index: number; keys: string[] }
    excludeKeys?: string[]
    mode?: 'default' | 'simple'
    placeholder?: string
    uploadConfig?: {
      maxFileSize?: number
      maxNumberOfFiles?: number
      server?: string
      isCustomUpload?: boolean
    }
  }

  const props = withDefaults(defineProps<Props>(), {
    height: '500px',
    mode: 'default',
    placeholder: '请输入内容...',
    excludeKeys: () => ['fontFamily']
  })

  const modelValue = defineModel<string>({ required: true })
  const editorRef = shallowRef<IDomEditor>()

  const DEFAULT_UPLOAD_CONFIG = {
    maxFileSize: 3 * 1024 * 1024,
    maxNumberOfFiles: 10,
    fieldName: 'file',
    allowedFileTypes: ['image/*']
  } as const

  const uploadServer = computed(() => props.uploadConfig?.server || `${VITE_API_URL}/upload/image`)

  const mergedUploadConfig = computed(() => ({
    ...DEFAULT_UPLOAD_CONFIG,
    ...props.uploadConfig
  }))

  const toolbarConfig = computed((): Partial<IToolbarConfig> => {
    const config: Partial<IToolbarConfig> = {}

    if (props.toolbarKeys?.length) {
      config.toolbarKeys = props.toolbarKeys
    }

    if (props.insertKeys) {
      config.insertKeys = props.insertKeys
    }

    if (props.excludeKeys?.length) {
      config.excludeKeys = props.excludeKeys
    }

    return config
  })

  const getUploadedUrl = (result: Record<string, any>) => result?.url || result?.data?.url || ''

  const uploadImage = async (file: File, insertFn: InsertFnType) => {
    try {
      const formData = new FormData()
      formData.append(mergedUploadConfig.value.fieldName, file)

      const result = await request.post<Api.Common.UploadFileResponse | Record<string, any>>({
        url: uploadServer.value,
        data: formData
      })

      const url = getUploadedUrl(result)
      if (!url) {
        throw new Error('上传成功，但未获取到图片地址')
      }

      insertFn(url, file.name, url)
      ElMessage.success(`图片上传成功 ${EmojiText[200]}`)
    } catch (error) {
      console.error('图片上传失败:', error)
      ElMessage.error(`图片上传失败 ${EmojiText[500]}`)
      throw error
    }
  }

  const editorConfig = computed(
    (): Partial<IEditorConfig> => ({
      placeholder: props.placeholder,
      MENU_CONF: {
        uploadImage: {
          fieldName: mergedUploadConfig.value.fieldName,
          maxFileSize: mergedUploadConfig.value.maxFileSize,
          maxNumberOfFiles: mergedUploadConfig.value.maxNumberOfFiles,
          allowedFileTypes: mergedUploadConfig.value.allowedFileTypes,
          server: uploadServer.value,
          customUpload: async (file: File, insertFn: InsertFnType) => {
            await uploadImage(file, insertFn)
          }
        }
      }
    })
  )

  const onCreateEditor = (editor: IDomEditor) => {
    editorRef.value = editor
  }

  defineExpose({
    getEditor: () => editorRef.value,
    setHtml: (html: string) => editorRef.value?.setHtml(html),
    getHtml: () => editorRef.value?.getHtml(),
    clear: () => editorRef.value?.clear(),
    focus: () => editorRef.value?.focus()
  })

  onBeforeUnmount(() => {
    editorRef.value?.destroy()
  })
</script>

<style lang="scss">
  @use './style';
</style>
