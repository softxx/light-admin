<template>
  <div class="file-manage-page art-full-height">
    <ArtSearchBar
      v-model="searchForm"
      :items="searchItems"
      :button-left-limit="0"
      :show-expand="false"
      @reset="handleReset"
      @search="handleSearch"
    />

    <ElCard class="art-table-card" style="margin-top: 12px">
      <ArtTableHeader v-model:columns="columnChecks" :loading="loading" @refresh="refreshData">
        <template #left>
          <div class="file-manage-actions">
            <ElUpload
              :show-file-list="false"
              :disabled="uploading"
              :before-upload="beforeUpload"
              :http-request="handleUpload"
            >
              <ElButton type="primary" :loading="uploading" v-ripple>上传文件</ElButton>
            </ElUpload>

            <ElButton
              type="danger"
              :disabled="!hasSelection"
              :loading="batchDeleting"
              @click="handleBatchDelete"
              v-ripple
            >
              批量删除
            </ElButton>
          </div>
        </template>
      </ArtTableHeader>

      <ArtTable
        ref="tableRef"
        :loading="loading"
        :data="data"
        :columns="columns"
        :pagination="pagination"
        rowKey="id"
        @selection-change="handleSelectionChange"
        @pagination:size-change="handleSizeChange"
        @pagination:current-change="handleCurrentChange"
      />
    </ElCard>

    <ElImageViewer
      v-if="previewVisible"
      :url-list="[previewImageUrl]"
      :initial-index="0"
      hide-on-click-modal
      @close="previewVisible = false"
    />
  </div>
</template>

<script setup lang="ts">
  import { h, markRaw } from 'vue'
  import type { UploadProps, UploadRequestOptions } from 'element-plus'
  import { ElImage, ElMessage, ElMessageBox, ElTag } from 'element-plus'
  import ArtButtonTable from '@/components/core/forms/art-button-table/index.vue'
  import { TableAdvancedFilter, TableQuickFilter } from '@/components/business/table-filters'
  import { useBatchDelete } from '@/hooks/core/useBatchDelete'
  import { useTable } from '@/hooks/core/useTable'
  import type { TableFilterFieldSchema, TableFilterFormModel, TableFilterGroup } from '@/types'
  import {
    fetchDeleteFile,
    fetchGetActiveUsers,
    fetchGetFileList,
    fetchUploadFile
  } from '@/api/system-manage'
  import { buildDynamicTableFilterParams, createTableFilterFormModel } from '@/utils/table/filter'

  defineOptions({ name: 'FileManage' })

  type FileItem = Api.SystemManage.FileListItem

  // 页面状态：表格选择、上传状态、图片预览状态。
  const tableRef = ref()
  const uploading = ref(false)
  const previewVisible = ref(false)
  const previewImageUrl = ref('')
  const selectedRows = ref<FileItem[]>([])
  const userOptions = ref<Array<{ label: string; value: number | string }>>([])
  const quickFilterRenderer = markRaw(TableQuickFilter)
  const advancedFilterRenderer = markRaw(TableAdvancedFilter)
  const searchForm = ref<TableFilterFormModel>(createTableFilterFormModel())

  // 浏览器可直接预览的图片类型。
  const imageExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg']

  // 文件管理页筛选字段，同时供快速筛选和高级筛选复用。
  const filterFields = computed<TableFilterFieldSchema[]>(() => [
    {
      label: '文件名称',
      value: 'filename',
      type: 'text',
      placeholder: '请输入文件名称'
    },
    {
      label: '扩展名',
      value: 'file_ext',
      type: 'text',
      placeholder: '请输入扩展名'
    },
    {
      label: 'MIME 类型',
      value: 'mime_type',
      type: 'text',
      placeholder: '请输入 MIME 类型'
    },
    {
      label: '文件大小',
      value: 'file_size',
      type: 'number',
      placeholder: '请输入文件大小'
    },
    {
      label: '上传用户',
      value: 'user_id',
      type: 'special-select',
      options: userOptions.value,
      placeholder: '请选择上传用户',
      containsPlaceholder: '请输入用户名或姓名'
    },
    {
      label: '上传时间',
      value: 'create_time',
      type: 'date',
      dateType: 'datetime',
      placeholder: '请选择上传时间'
    }
  ])

  const searchItems = computed(() => [
    {
      label: '快速过滤',
      labelWidth: '84px',
      key: 'quickFilter',
      span: 12,
      render: quickFilterRenderer,
      props: {
        fields: filterFields.value
      }
    },
    {
      label: '高级过滤',
      labelWidth: '84px',
      key: 'advancedFilters',
      span: 6,
      render: advancedFilterRenderer,
      props: {
        fields: filterFields.value,
        onApply: handleAdvancedFilterApply
      }
    }
  ])

  // 后端已有 MIME 类型时优先按 MIME 判断，缺失时用扩展名兜底。
  const isImageFile = (row: FileItem) => {
    const mimeType = String(row.mime_type || '').toLowerCase()
    const ext = String(row.file_ext || '').toLowerCase()
    return mimeType.startsWith('image/') || imageExts.includes(ext)
  }

  // 兼容历史数据里可能存在的 Windows 反斜杠 URL。
  const normalizeUrl = (url?: string) => String(url || '').replace(/\\/g, '/')

  // 文件大小展示统一转成人可读格式。
  const formatBytes = (bytes?: number) => {
    const value = Number(bytes || 0)
    if (!value) {
      return '0 B'
    }

    const units = ['B', 'KB', 'MB', 'GB']
    let size = value
    let index = 0

    while (size >= 1024 && index < units.length - 1) {
      size /= 1024
      index += 1
    }

    return `${size >= 10 || index === 0 ? size.toFixed(0) : size.toFixed(1)} ${units[index]}`
  }

  // 图片显示缩略图，其他文件显示扩展名方块。
  const renderPreview = (row: FileItem) => {
    if (isImageFile(row)) {
      const imageUrl = normalizeUrl(row.url)
      return h(
        ElImage,
        {
          class: 'file-manage-thumb',
          src: imageUrl,
          fit: 'cover',
          previewSrcList: [imageUrl],
          previewTeleported: true,
          hideOnClickModal: true
        },
        {
          error: () => h('div', { class: 'file-manage-thumb__fallback' }, 'IMG')
        }
      )
    }

    return h('div', { class: 'file-manage-filebox' }, String(row.file_ext || 'FILE').slice(0, 4).toUpperCase())
  }

  // 文件名列只显示业务文件名，资源地址放到独立隐藏列和复制按钮里。
  const renderFileName = (row: FileItem) =>
    h('div', { class: 'file-manage-name-cell' }, [
      h('div', { class: 'file-manage-name' }, row.filename || '-'),
    ])

  // 文件地址默认不占表格主视图，需要时可通过列设置打开。
  const renderFileUrl = (row: FileItem) =>
    h('span', { class: 'file-manage-url' }, normalizeUrl(row.url) || '-')

  const renderExtTag = (row: FileItem) =>
    h(
      ElTag,
      {
        type: isImageFile(row) ? 'success' : 'info',
        effect: 'light'
      },
      () => String(row.file_ext || '-').toUpperCase()
    )

  // 复制链接兼容不支持 Clipboard API 的旧浏览器环境。
  const copyText = async (text: string) => {
    if (navigator.clipboard?.writeText) {
      await navigator.clipboard.writeText(text)
      return
    }

    const textarea = document.createElement('textarea')
    textarea.value = text
    textarea.style.position = 'fixed'
    textarea.style.opacity = '0'
    document.body.appendChild(textarea)
    textarea.select()
    document.execCommand('copy')
    document.body.removeChild(textarea)
  }

  // 复制文件 URL。
  const handleCopyUrl = async (row: FileItem) => {
    await copyText(normalizeUrl(row.url))
    ElMessage.success('链接已复制')
  }

  // 只对图片启用预览，其他文件暂不打开预览弹层。
  const handlePreview = (row: FileItem) => {
    if (!isImageFile(row)) {
      ElMessage.warning('当前文件暂不支持预览')
      return
    }

    previewImageUrl.value = normalizeUrl(row.url)
    previewVisible.value = true
  }

  // 单文件删除：删除前提示会同步移除文件记录和本地文件。
  const handleDelete = async (row: FileItem) => {
    await ElMessageBox.confirm(
      `确定删除文件「${row.filename || row.id}」吗？删除后文件记录和本地文件都会移除。`,
      '删除确认',
      {
        type: 'warning',
        confirmButtonText: '确定',
        cancelButtonText: '取消'
      }
    )

    await fetchDeleteFile(row.id)
    await refreshRemove()
  }

  // 复用项目表格 Hook，保持分页、刷新、列配置和其他列表页一致。
  const {
    columns,
    columnChecks,
    data,
    loading,
    pagination,
    getData,
    replaceSearchParams,
    resetSearchParams,
    handleSizeChange,
    handleCurrentChange,
    refreshData,
    refreshCreate,
    refreshRemove
  } = useTable({
    core: {
      apiFn: fetchGetFileList,
      apiParams: {
        page: 1,
        pageSize: 20
      },
      columnsFactory: () => [
        { type: 'selection' as const, width: 55, fixed: 'left' as const, disabled: true },
        { type: 'index', label: '序号', width: 70 },
        {
          prop: 'preview',
          label: '预览',
          width: 92,
          formatter: renderPreview
        },
        {
          prop: 'filename',
          label: '文件名称',
          minWidth: 220,
          formatter: renderFileName
        },
        {
          prop: 'url',
          label: '文件地址',
          minWidth: 280,
          visible: false,
          formatter: renderFileUrl
        },
        {
          prop: 'file_ext',
          label: '类型',
          width: 100,
          formatter: renderExtTag
        },
        { prop: 'mime_type', label: 'MIME 类型', minWidth: 150 },
        {
          prop: 'file_size',
          label: '大小',
          width: 110,
          formatter: (row: FileItem) => formatBytes(row.file_size)
        },
        {
          prop: 'realname',
          label: '上传用户',
          minWidth: 120,
          formatter: (row: FileItem) => row.realname || row.user?.realname || row.username || row.user?.username || '-'
        },
        { prop: 'create_time', label: '上传时间', minWidth: 170 },
        {
          prop: 'operation',
          label: '操作',
          width: 140,
          fixed: 'right',
          formatter: (row: FileItem) =>
            h(
              'div',
              { class: 'file-manage-row-actions' },
              [
                isImageFile(row)
                  ? h(ArtButtonTable, {
                      type: 'view',
                      tooltip: '预览图片',
                      onClick: () => handlePreview(row)
                    })
                  : null,
                h(ArtButtonTable, {
                  icon: 'ri:file-copy-line',
                  tooltip: '复制链接',
                  iconClass: 'bg-primary/12 text-primary',
                  onClick: () => handleCopyUrl(row)
                }),
                h(ArtButtonTable, {
                  type: 'delete',
                  onClick: () => handleDelete(row)
                })
              ].filter(Boolean)
            )
        }
      ]
    }
  })

  // 批量删除沿用公共 Hook，每个文件逐条调用删除接口。
  const { batchDeleting, hasSelection, handleSelectionChange, handleBatchDelete } =
    useBatchDelete<FileItem>({
      selectedRows,
      getLabel: (row) => row.filename || `ID ${row.id}`,
      deleteFn: (row) =>
        fetchDeleteFile(row.id, {
          showSuccessMessage: false,
          showErrorMessage: false
        }),
      refreshFn: refreshRemove,
      clearSelection: () => tableRef.value?.elTableRef?.clearSelection?.()
    })

  // 把筛选组件的模型序列化为后端 DynamicFilterSearchTrait 可识别的参数。
  const performSearch = (params: Partial<TableFilterFormModel>) => {
    replaceSearchParams(buildDynamicTableFilterParams(params, filterFields.value))
    getData()
  }

  // 快速筛选提交。
  const handleSearch = (params: Record<string, any>) => {
    performSearch(params as TableFilterFormModel)
  }

  // 高级筛选弹层提交。
  const handleAdvancedFilterApply = (advancedFilters: TableFilterGroup[]) => {
    performSearch({
      quickFilter: searchForm.value.quickFilter,
      advancedFilters
    })
  }

  // 重置筛选并重新加载列表。
  const handleReset = async () => {
    await resetSearchParams()
  }

  // 与后端上传配置保持一致，前端先限制 10MB 以内。
  const beforeUpload: UploadProps['beforeUpload'] = (file) => {
    if (file.size / 1024 / 1024 > 10) {
      ElMessage.error('文件大小不能超过 10MB')
      return false
    }

    return true
  }

  // 文件上传成功后刷新到第一页，便于立刻看到新文件。
  const handleUpload = async (options: UploadRequestOptions) => {
    uploading.value = true

    try {
      const formData = new FormData()
      formData.append('file', options.file)
      const result = await fetchUploadFile(formData)
      options.onSuccess(result)
      ElMessage.success('上传成功')
      await refreshCreate()
    } catch (error) {
      const uploadError = Object.assign(
        error instanceof Error ? error : new Error('上传失败'),
        {
          status: 500,
          method: 'POST',
          url: '/upload/file'
        }
      )
      options.onError(uploadError as any)
    } finally {
      uploading.value = false
    }
  }

  // 加载用户选项，供上传人筛选使用。
  const loadUsers = async () => {
    const users = await fetchGetActiveUsers({
      page: 1,
      pageSize: 500
    })
    const list = users.list || users.data || users.records || []
    userOptions.value = list.map((item: any) => ({
      label: item.realname || item.username || `用户 ${item.id}`,
      value: item.id
    }))
  }

  onMounted(() => {
    loadUsers()
  })
</script>

<style scoped lang="scss">
  .file-manage-actions {
    display: inline-flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 10px;
  }

  .file-manage-name-cell {
    display: flex;
    flex-direction: column;
    gap: 4px;
    min-width: 0;
  }

  .file-manage-name {
    color: var(--el-text-color-primary);
    font-weight: 500;
    line-height: 1.4;
    word-break: break-word;
  }

  .file-manage-url {
    color: var(--el-text-color-secondary);
    font-size: 12px;
    line-height: 1.4;
    word-break: break-all;
  }

  :deep(.file-manage-thumb) {
    display: block;
    width: 48px;
    height: 48px;
    overflow: hidden;
    border: 1px solid var(--el-border-color-lighter);
    border-radius: 6px;
    background: var(--el-fill-color-light);
    cursor: zoom-in;
  }

  :deep(.file-manage-thumb__fallback),
  :deep(.file-manage-filebox) {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 48px;
    height: 48px;
    border: 1px solid var(--el-border-color-lighter);
    border-radius: 6px;
    background: var(--el-fill-color-light);
    color: var(--el-text-color-secondary);
    font-size: 11px;
    font-weight: 700;
  }

  :deep(.file-manage-row-actions) {
    display: inline-flex;
    align-items: center;
  }
</style>
