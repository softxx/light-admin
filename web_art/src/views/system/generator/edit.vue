<template>
  <div class="art-full-height" v-loading="loading">
    <ElCard class="art-table-card">
      <div class="flex-cb mb-4">
        <div>
          <div class="text-lg font-semibold">生成配置</div>
          <div class="text-sm text-[var(--art-gray-500)]">{{ form.table_name || route.params.id }}</div>
        </div>
        <div class="flex gap-2">
          <ElButton @click="router.back()">返回</ElButton>
          <ElButton @click="handlePreview">预览代码</ElButton>
          <ElButton @click="handleSave">保存</ElButton>
          <ElButton type="primary" @click="handleGenerate">生成代码</ElButton>
        </div>
      </div>

      <ElTabs v-model="activeTab">
        <ElTabPane label="生成配置" name="base">
          <ElForm ref="formRef" :model="form" :rules="rules" label-width="100px">
            <ElRow :gutter="16">
              <ElCol :span="12">
                <ElFormItem label="表名称" prop="table_name">
                  <ElInput v-model="form.table_name" />
                </ElFormItem>
              </ElCol>
              <ElCol :span="12">
                <ElFormItem label="表描述" prop="table_comment">
                  <ElInput v-model="form.table_comment" />
                </ElFormItem>
              </ElCol>
              <ElCol :span="12">
                <ElFormItem label="应用目录" prop="module_name">
                  <ElInput v-model="form.module_name" />
                </ElFormItem>
              </ElCol>
              <ElCol :span="12">
                <ElFormItem label="模块目录" prop="class_dir">
                  <ElInput v-model="form.class_dir" />
                </ElFormItem>
              </ElCol>
              <ElCol :span="12">
                <ElFormItem label="生成方式" prop="generate_type">
                  <ElRadioGroup v-model="form.generate_type">
                    <ElRadio :value="0">压缩包下载</ElRadio>
                    <ElRadio :value="1">生成到模块</ElRadio>
                  </ElRadioGroup>
                </ElFormItem>
              </ElCol>
              <ElCol :span="12">
                <ElFormItem label="删除方式" prop="delete_type">
                  <ElRadioGroup v-model="form.delete_type">
                    <ElRadio :value="0">真删除</ElRadio>
                    <ElRadio :value="1">软删除</ElRadio>
                  </ElRadioGroup>
                </ElFormItem>
              </ElCol>
              <ElCol :span="12">
                <ElFormItem label="父级菜单" prop="menu_pid">
                  <ElTreeSelect
                    v-model="form.menu_pid"
                    :data="menuTree"
                    clearable
                    filterable
                    node-key="id"
                    style="width: 100%"
                    placeholder="请选择父级菜单"
                    :props="treeProps"
                  />
                </ElFormItem>
              </ElCol>
              <ElCol :span="12">
                <ElFormItem label="菜单名称" prop="menu_name">
                  <ElInput v-model="form.menu_name" />
                </ElFormItem>
              </ElCol>
              <ElCol :span="12">
                <ElFormItem label="菜单构建" prop="menu_type">
                  <ElRadioGroup v-model="form.menu_type">
                    <ElRadio :value="1">自动构建</ElRadio>
                    <ElRadio :value="0">手动添加</ElRadio>
                  </ElRadioGroup>
                </ElFormItem>
              </ElCol>
            </ElRow>
          </ElForm>
        </ElTabPane>

        <ElTabPane label="字段管理" name="field">
          <ElTable :data="tableColumns" border>
            <ElTableColumn prop="name" label="字段名" min-width="140" />
            <ElTableColumn label="字段描述" min-width="180">
              <template #default="{ row }">
                <ElInput v-model="row.comment" />
              </template>
            </ElTableColumn>
            <ElTableColumn prop="type" label="字段类型" width="110" />
            <ElTableColumn label="必填" width="80">
              <template #default="{ row }">
                <ElSwitch v-model="row.is_required" :active-value="1" :inactive-value="0" />
              </template>
            </ElTableColumn>
            <ElTableColumn label="插入" width="80">
              <template #default="{ row }">
                <ElSwitch v-model="row.is_insert" :active-value="1" :inactive-value="0" />
              </template>
            </ElTableColumn>
            <ElTableColumn label="列表" width="80">
              <template #default="{ row }">
                <ElSwitch v-model="row.is_list" :active-value="1" :inactive-value="0" />
              </template>
            </ElTableColumn>
            <ElTableColumn label="查询" width="80">
              <template #default="{ row }">
                <ElSwitch v-model="row.is_search" :active-value="1" :inactive-value="0" />
              </template>
            </ElTableColumn>
            <ElTableColumn label="查询方式" min-width="140">
              <template #default="{ row }">
                <ElSelect v-model="row.search_type" clearable style="width: 100%">
                  <ElOption
                    v-for="item in searchTypeOptions"
                    :key="item.value"
                    :label="item.label"
                    :value="item.value"
                  />
                </ElSelect>
              </template>
            </ElTableColumn>
            <ElTableColumn label="组件类型" min-width="140">
              <template #default="{ row }">
                <ElSelect v-model="row.show_type" clearable style="width: 100%">
                  <ElOption
                    v-for="item in showTypeOptions"
                    :key="item.value"
                    :label="item.label"
                    :value="item.value"
                  />
                </ElSelect>
              </template>
            </ElTableColumn>
            <ElTableColumn label="字典类型" min-width="160">
              <template #default="{ row }">
                <ElSelect v-model="row.dict_type" clearable filterable style="width: 100%">
                  <ElOption
                    v-for="item in dictTypeOptions"
                    :key="item.value"
                    :label="item.name"
                    :value="item.value"
                  />
                </ElSelect>
              </template>
            </ElTableColumn>
            <ElTableColumn label="操作" width="80" fixed="right">
              <template #default="{ row, $index }">
                <ElButton type="danger" text @click="handleDeleteField(row, $index)">删除</ElButton>
              </template>
            </ElTableColumn>
          </ElTable>
        </ElTabPane>
      </ElTabs>
    </ElCard>

    <PreviewDialog v-model="previewVisible" :preview-data="previewData" />
  </div>
</template>

<script setup lang="ts">
  import { ElMessageBox, type FormInstance, type FormRules } from 'element-plus'
  import {
    fetchDeleteGeneratorField,
    fetchGetDictList,
    fetchGetGeneratorDetail,
    fetchGetMenuList,
    fetchMakeGeneratorCode,
    fetchPreviewGeneratorCode,
    fetchSaveGenerator
  } from '@/api/system-manage'
  import PreviewDialog from './modules/preview-dialog.vue'

  defineOptions({ name: 'GeneratorEdit' })

  const route = useRoute()
  const router = useRouter()
  const formRef = ref<FormInstance>()
  const loading = ref(false)
  const activeTab = ref('base')
  const previewVisible = ref(false)
  const previewData = ref<Record<string, any>>({})
  const tableColumns = ref<any[]>([])
  const dictTypeOptions = ref<Api.SystemManage.DictListItem[]>([])
  const menuTree = ref<any[]>([])

  const treeProps = {
    label: 'title',
    value: 'id',
    children: 'children'
  }

  const form = reactive<Record<string, any>>({
    id: route.params.id,
    table_name: '',
    table_comment: '',
    module_name: 'adminapi',
    class_dir: '',
    generate_type: 0,
    delete_type: 0,
    menu_pid: 0,
    menu_name: '',
    menu_type: 1
  })

  const rules = reactive<FormRules>({
    table_name: [{ required: true, message: '请输入表名称', trigger: 'blur' }],
    table_comment: [{ required: true, message: '请输入表描述', trigger: 'blur' }],
    module_name: [{ required: true, message: '请输入应用目录', trigger: 'blur' }],
    class_dir: [{ required: true, message: '请输入模块目录', trigger: 'blur' }],
    menu_pid: [{ required: true, message: '请选择父级菜单', trigger: 'change' }],
    menu_name: [{ required: true, message: '请输入菜单名称', trigger: 'blur' }]
  })

  const searchTypeOptions = [
    { label: '等于', value: '=' },
    { label: '模糊查询', value: 'like' },
    { label: '时间范围', value: 'between time' },
    { label: '范围查询', value: 'between' },
    { label: 'IN 查询', value: 'in' },
    { label: '不等于', value: '!=' },
    { label: '大于', value: '>' },
    { label: '大于等于', value: '>=' },
    { label: '小于', value: '<' },
    { label: '小于等于', value: '<=' }
  ]

  const showTypeOptions = [
    { label: '文本框', value: 'input' },
    { label: '文本域', value: 'textarea' },
    { label: '下拉框', value: 'select' },
    { label: '单选框', value: 'radio' },
    { label: '复选框', value: 'checkbox' },
    { label: '日期控件', value: 'datePicker' },
    { label: '富文本', value: 'editor' }
  ]

  const loadDetail = async () => {
    loading.value = true
    try {
      const [detail, dictTypes, menus] = await Promise.all([
        fetchGetGeneratorDetail(route.params.id as string),
        fetchGetDictList({ type: 'dict_type' }),
        fetchGetMenuList()
      ])

      Object.assign(form, {
        id: detail.id,
        table_name: detail.table_name || '',
        table_comment: detail.table_comment || '',
        module_name: detail.module_name || 'adminapi',
        class_dir: detail.class_dir || '',
        generate_type: Number(detail.generate_type || 0),
        delete_type: Number(detail.delete_type || 0),
        menu_pid: Number(detail.menu_pid || 0),
        menu_name: detail.menu_name || '',
        menu_type: Number(detail.menu_type || 1)
      })

      tableColumns.value = Array.isArray(detail.table_column) ? detail.table_column : []
      dictTypeOptions.value = dictTypes
      menuTree.value = [
        {
          id: 0,
          title: '顶级菜单',
          children: menus
        }
      ]
    } finally {
      loading.value = false
    }
  }

  const saveConfig = async () => {
    if (!formRef.value) {
      return
    }

    await formRef.value.validate()
    await fetchSaveGenerator(route.params.id as string, {
      ...form,
      column: tableColumns.value
    })
  }

  const handleSave = async () => {
    await saveConfig()
  }

  const handlePreview = async () => {
    await saveConfig()
    previewData.value = await fetchPreviewGeneratorCode(route.params.id as string)
    previewVisible.value = true
  }

  const handleGenerate = async () => {
    await saveConfig()
    const result = await fetchMakeGeneratorCode(route.params.id as string)
    await ElMessageBox.alert(result?.file ? `代码已生成，文件标识：${result.file}` : '代码生成成功', '生成成功', {
      confirmButtonText: '知道了'
    })
  }

  const handleDeleteField = async (row: any, index: number) => {
    if (row.id) {
      await fetchDeleteGeneratorField(row.id)
    }
    tableColumns.value.splice(index, 1)
  }

  onMounted(() => {
    loadDetail()
  })
</script>
