<template>
  <ElDialog
    v-model="visible"
    :title="dialogTitle"
    class="user-dialog"
    width="min(760px, calc(100vw - 24px))"
    align-center
    @closed="handleClosed"
  >
    <ElForm
      ref="formRef"
      v-loading="loading"
      class="user-form"
      :model="form"
      :rules="rules"
      label-width="90px"
    >
      <ElRow :gutter="16">
        <ElCol :xs="24" :sm="12">
          <ElFormItem label="用户名" prop="username">
            <ElInput
              v-model="form.username"
              :disabled="props.type === 'edit'"
              placeholder="请输入用户名"
            />
          </ElFormItem>
        </ElCol>

        <ElCol :xs="24" :sm="12">
          <ElFormItem label="姓名" prop="realname">
            <ElInput v-model="form.realname" placeholder="请输入姓名" />
          </ElFormItem>
        </ElCol>

        <ElCol :xs="24" :sm="12">
          <ElFormItem label="手机号" prop="phone">
            <ElInput v-model="form.phone" placeholder="请输入手机号" />
          </ElFormItem>
        </ElCol>

        <ElCol :xs="24" :sm="12">
          <ElFormItem label="邮箱" prop="email">
            <ElInput v-model="form.email" placeholder="请输入邮箱" />
          </ElFormItem>
        </ElCol>

        <ElCol :span="24">
          <ElFormItem label="头像" prop="avatar">
            <div class="w-full flex flex-col gap-3">
              <BackendImageUpload v-model="form.avatar" />
              <ElInput
                v-model="form.avatar"
                placeholder="上传后会自动回填，也可以直接填写头像地址"
              />
            </div>
          </ElFormItem>
        </ElCol>
      </ElRow>
    </ElForm>

    <template v-if="props.showPermission">
      <ElDivider content-position="left">权限设置</ElDivider>

      <ElAlert
        v-if="isProtectedUser"
        title="管理员账号默认拥有全部权限，不支持维护。"
        type="info"
        :closable="false"
        style="margin-bottom: 12px"
      />

      <div class="permission-tree-panel">
        <div class="permission-tree-toolbar">
          <ElButton
            size="small"
            :disabled="permissionLoading || !treeData.length"
            @click="togglePermissionTreeExpanded"
            v-ripple
          >
            <ArtSvgIcon
              :icon="permissionTreeExpanded ? 'ri:arrow-up-wide-fill' : 'ri:arrow-down-wide-fill'"
              class="mr-1"
            />
            {{ permissionTreeExpanded ? '收起' : '展开' }}
          </ElButton>
        </div>

        <ElScrollbar height="320px">
          <ElTree
            ref="treeRef"
            v-loading="permissionLoading"
            :data="treeData"
            node-key="id"
            show-checkbox
            default-expand-all
            :props="treeProps"
            empty-text="暂无权限节点"
            @check="handleCheck"
            @node-expand="syncPermissionTreeExpanded"
            @node-collapse="syncPermissionTreeExpanded"
          />
        </ElScrollbar>
      </div>
    </template>

    <template #footer>
      <ElButton @click="visible = false">取消</ElButton>
      <ElButton
        type="primary"
        :loading="submitting"
        :disabled="loading || permissionLoading"
        @click="handleSubmit"
      >
        保存
      </ElButton>
    </template>
  </ElDialog>
</template>

<script setup lang="ts">
  import type { ElTree, FormInstance, FormRules } from 'element-plus'
  import {
    fetchGetPermissionTree,
    fetchGetUserDetail,
    fetchGetUserPermission,
    fetchSaveUser
  } from '@/api/system-manage'

  type DialogType = 'add' | 'edit'
  type PermissionTreeNode = Api.Backend.AuthTreeNode & {
    disabled?: boolean
    children?: PermissionTreeNode[]
  }
  type TreeNodeInstance = {
    childNodes?: TreeNodeInstance[]
    expand?: (callback?: (() => void) | null, expandParent?: boolean) => void
    collapse?: () => void
  }

  interface Props {
    visible: boolean
    type: DialogType
    userData?: Partial<Api.SystemManage.UserListItem>
    showPermission?: boolean
  }

  interface Emits {
    (e: 'update:visible', value: boolean): void
    (e: 'success', type: DialogType): void
  }

  const props = withDefaults(defineProps<Props>(), {
    visible: false,
    type: 'add',
    userData: () => ({}),
    showPermission: false
  })

  const emit = defineEmits<Emits>()

  const formRef = ref<FormInstance>()
  const treeRef = ref<InstanceType<typeof ElTree>>()
  const loading = ref(false)
  const permissionLoading = ref(false)
  const submitting = ref(false)
  const treeData = ref<PermissionTreeNode[]>([])
  const checkedKeys = ref<Array<number | string>>([])
  const permissionTreeExpanded = ref(true)

  const visible = computed({
    get: () => props.visible,
    set: (value) => emit('update:visible', value)
  })

  const dialogTitle = computed(() =>
    props.type === 'add' ? '新增用户（默认密码：123456）' : '编辑用户'
  )

  const treeProps = {
    label: 'title',
    children: 'children',
    disabled: 'disabled'
  }

  // 部门和角色字段已移除，新增/编辑用户维护基础信息和直接挂到用户的权限。
  const form = reactive<
    Api.SystemManage.UserPayload & {
      username: string
    }
  >({
    id: undefined,
    username: '',
    realname: '',
    phone: '',
    email: '',
    avatar: ''
  })

  const rules = reactive<FormRules>({
    username: [
      {
        validator: (_rule, value, callback) => {
          if (props.type === 'add' && !value) {
            callback(new Error('请输入用户名'))
            return
          }
          callback()
        },
        trigger: 'blur'
      }
    ],
    realname: [{ required: true, message: '请输入姓名', trigger: 'blur' }],
    email: [{ type: 'email', message: '邮箱格式不正确', trigger: 'blur' }]
  })

  const isProtectedUser = computed(
    () =>
      props.type === 'edit' &&
      (Number(props.userData?.is_admin || 0) === 1 ||
        String(props.userData?.username || '').toLowerCase() === 'admin')
  )

  const setTreeDisabled = (
    nodes: Api.Backend.AuthTreeNode[] = [],
    disabled = false
  ): PermissionTreeNode[] =>
    nodes.map((node) => ({
      ...node,
      disabled,
      children: setTreeDisabled(node.children || [], disabled)
    }))

  const syncCheckedKeys = () => {
    // 只提交叶子节点，后端会补齐父级菜单，避免树组件父子级联造成冗余。
    checkedKeys.value = (treeRef.value?.getCheckedKeys(true) as Array<number | string>) || []
  }

  const setPermissionTreeExpanded = (expanded: boolean) => {
    const rootNodes =
      (
        treeRef.value as unknown as {
          store?: { root?: { childNodes?: TreeNodeInstance[] } }
        }
      )?.store?.root?.childNodes || []

    const toggleNode = (node: TreeNodeInstance) => {
      if (expanded) {
        node.expand?.(null, false)
      } else {
        node.collapse?.()
      }

      node.childNodes?.forEach(toggleNode)
    }

    rootNodes.forEach(toggleNode)
    permissionTreeExpanded.value = expanded
  }

  const isEveryPermissionNodeExpanded = () => {
    const rootNodes =
      (
        treeRef.value as unknown as {
          store?: { root?: { childNodes?: TreeNodeInstance[] } }
        }
      )?.store?.root?.childNodes || []

    const isExpanded = (node: TreeNodeInstance): boolean => {
      if (!node.childNodes?.length) {
        return true
      }

      return (
        node.childNodes.every(isExpanded) &&
        node.childNodes.every((child) => {
          if (!child.childNodes?.length) {
            return true
          }

          return Boolean((child as TreeNodeInstance & { expanded?: boolean }).expanded)
        })
      )
    }

    return rootNodes.every((node) => {
      if (!node.childNodes?.length) {
        return true
      }

      return (
        Boolean((node as TreeNodeInstance & { expanded?: boolean }).expanded) && isExpanded(node)
      )
    })
  }

  const syncPermissionTreeExpanded = () => {
    nextTick(() => {
      permissionTreeExpanded.value = isEveryPermissionNodeExpanded()
    })
  }

  const togglePermissionTreeExpanded = () => {
    setPermissionTreeExpanded(!permissionTreeExpanded.value)
  }

  const resetForm = () => {
    Object.assign(form, {
      id: undefined,
      username: '',
      realname: '',
      phone: '',
      email: '',
      avatar: ''
    })
  }

  const resetPermissionTree = () => {
    treeRef.value?.setCheckedKeys([], true)
    treeData.value = []
    checkedKeys.value = []
  }

  const loadPermissionTree = async () => {
    if (!props.showPermission) {
      resetPermissionTree()
      return
    }

    permissionLoading.value = true
    try {
      const data =
        props.type === 'edit' && props.userData?.id
          ? await fetchGetUserPermission(props.userData.id)
          : await fetchGetPermissionTree()

      treeData.value = setTreeDisabled(
        Array.isArray(data.authNode) ? data.authNode : [],
        isProtectedUser.value
      )

      await nextTick()
      treeRef.value?.setCheckedKeys(Array.isArray(data.checked) ? data.checked : [], true)
      syncCheckedKeys()
      permissionTreeExpanded.value = true
    } finally {
      permissionLoading.value = false
    }
  }

  const fillForm = async () => {
    resetForm()

    loading.value = true
    try {
      if (props.type === 'edit' && props.userData?.id) {
        const detail = await fetchGetUserDetail(props.userData.id)
        Object.assign(form, {
          id: detail.id,
          username: detail.username || '',
          realname: detail.realname || '',
          phone: detail.phone || '',
          email: detail.email || '',
          avatar: detail.avatar || ''
        })
      }

      await loadPermissionTree()
    } finally {
      loading.value = false
    }
  }

  const handleCheck = () => {
    syncCheckedKeys()
  }

  const handleSubmit = async () => {
    if (!formRef.value || loading.value || permissionLoading.value) {
      return
    }

    await formRef.value.validate()

    const payload: Api.SystemManage.UserPayload = {
      id: form.id,
      username: form.username,
      realname: form.realname,
      phone: form.phone,
      email: form.email,
      avatar: form.avatar
    }

    if (props.showPermission) {
      payload.menu_id = checkedKeys.value
    }

    submitting.value = true
    try {
      await fetchSaveUser(payload)
      visible.value = false
      emit('success', props.type)
    } finally {
      submitting.value = false
    }
  }

  const handleClosed = () => {
    formRef.value?.clearValidate()
    resetForm()
    resetPermissionTree()
  }

  watch(
    () => props.visible,
    (value) => {
      if (!value) {
        return
      }

      fillForm().then(() => {
        nextTick(() => {
          formRef.value?.clearValidate()
        })
      })
    }
  )
</script>

<style scoped>
  .permission-tree-panel {
    padding: 8px 12px;
    border: 1px solid var(--el-border-color-light);
    border-radius: 6px;
  }

  .permission-tree-toolbar {
    display: flex;
    gap: 8px;
    justify-content: flex-end;
    margin-bottom: 8px;
  }

  .user-form :deep(.el-input) {
    width: 100%;
  }

  @media (max-width: 767px) {
    :global(.user-dialog.el-dialog) {
      max-width: calc(100vw - 24px);
      margin-top: 5vh !important;
      margin-bottom: 5vh !important;
    }

    .user-form :deep(.el-form-item) {
      margin-bottom: 16px;
    }

    .user-form :deep(.el-form-item__content) {
      min-width: 0;
    }
  }
</style>
