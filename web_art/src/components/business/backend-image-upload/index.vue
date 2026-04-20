<template>
  <div class="backend-image-upload">
    <ElUpload
      class="backend-image-upload__trigger"
      :show-file-list="false"
      accept="image/*"
      :disabled="disabled || uploading"
      :http-request="handleUpload"
      :before-upload="beforeUpload"
    >
      <div class="backend-image-upload__card" :class="{ 'is-round': round }">
        <img
          v-if="imageUrl"
          :src="imageUrl"
          alt="uploaded-image"
          class="backend-image-upload__image"
          :class="{ 'is-round': round }"
        />
        <div v-else class="backend-image-upload__placeholder">
          <ElIcon :size="28">
            <Plus />
          </ElIcon>
          <span>{{ placeholder }}</span>
        </div>

        <div class="backend-image-upload__mask">
          <ElIcon :size="18">
            <UploadFilled />
          </ElIcon>
          <span>{{ uploading ? '上传中...' : imageUrl ? '重新上传' : '上传图片' }}</span>
        </div>
      </div>
    </ElUpload>

    <div class="backend-image-upload__footer">
      <span class="backend-image-upload__tip">{{ tip }}</span>
      <ElButton
        v-if="imageUrl"
        text
        type="primary"
        :disabled="disabled || uploading"
        @click.stop="clearImage"
      >
        清空
      </ElButton>
    </div>
  </div>
</template>

<script setup lang="ts">
  import { Plus, UploadFilled } from '@element-plus/icons-vue'
  import type { UploadProps, UploadRequestOptions } from 'element-plus'
  import { fetchUploadImage } from '@/api/system-manage'

  defineOptions({ name: 'BackendImageUpload' })

  interface Props {
    modelValue?: string
    disabled?: boolean
    round?: boolean
    placeholder?: string
    tip?: string
    maxSizeMb?: number
  }

  interface Emits {
    (e: 'update:modelValue', value: string): void
    (e: 'success', value: Api.Common.UploadFileResponse): void
  }

  const props = withDefaults(defineProps<Props>(), {
    modelValue: '',
    disabled: false,
    round: true,
    placeholder: '点击上传',
    tip: '支持 jpg/png/gif/webp，大小不超过 10MB',
    maxSizeMb: 10
  })

  const emit = defineEmits<Emits>()
  const uploading = ref(false)

  const imageUrl = computed({
    get: () => props.modelValue || '',
    set: (value: string) => emit('update:modelValue', value)
  })

  const beforeUpload: UploadProps['beforeUpload'] = (file) => {
    if (!file.type.startsWith('image/')) {
      ElMessage.error('只能上传图片文件')
      return false
    }

    if (file.size / 1024 / 1024 > props.maxSizeMb) {
      ElMessage.error(`图片大小不能超过 ${props.maxSizeMb}MB`)
      return false
    }

    return true
  }

  const handleUpload = async (options: UploadRequestOptions) => {
    uploading.value = true

    try {
      const formData = new FormData()
      formData.append('file', options.file)

      const result = await fetchUploadImage(formData)
      imageUrl.value = result.url || ''
      emit('success', result)
      options.onSuccess(result)
    } catch (error) {
      const uploadError = Object.assign(
        error instanceof Error ? error : new Error('图片上传失败'),
        {
          status: 0,
          method: 'post',
          url: '/upload/image'
        }
      )

      options.onError(uploadError as any)
    } finally {
      uploading.value = false
    }
  }

  const clearImage = () => {
    imageUrl.value = ''
  }
</script>

<style scoped lang="scss">
  .backend-image-upload {
    display: inline-flex;
    flex-direction: column;
    gap: 10px;
  }

  .backend-image-upload__card {
    position: relative;
    width: 132px;
    height: 132px;
    overflow: hidden;
    border: 1px dashed var(--el-border-color);
    border-radius: 16px;
    background: var(--el-fill-color-lighter);
    transition: all 0.2s ease;

    &.is-round {
      border-radius: 999px;
    }

    &:hover {
      border-color: var(--el-color-primary);
      transform: translateY(-1px);
    }
  }

  .backend-image-upload__image,
  .backend-image-upload__placeholder,
  .backend-image-upload__mask {
    position: absolute;
    inset: 0;
  }

  .backend-image-upload__image {
    width: 100%;
    height: 100%;
    object-fit: cover;

    &.is-round {
      border-radius: 999px;
    }
  }

  .backend-image-upload__placeholder {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 8px;
    color: var(--el-text-color-secondary);
    font-size: 13px;
  }

  .backend-image-upload__mask {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 6px;
    color: #fff;
    font-size: 12px;
    background: rgb(0 0 0 / 48%);
    opacity: 0;
    transition: opacity 0.2s ease;
  }

  .backend-image-upload__card:hover .backend-image-upload__mask {
    opacity: 1;
  }

  .backend-image-upload__footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
  }

  .backend-image-upload__tip {
    color: var(--el-text-color-secondary);
    font-size: 12px;
    line-height: 1.5;
  }

  .backend-image-upload :deep(.el-upload) {
    border: none;
    background: transparent;
  }
</style>
