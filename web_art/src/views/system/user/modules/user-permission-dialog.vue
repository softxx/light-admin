<template>
  <ElDialog v-model="visible" title="用户权限" width="560px" align-center @closed="handleClosed">
    <ElAlert
      v-if="isProtectedUser"
      title="管理员账号默认拥有全部权限，不支持维护。"
      type="info"
      :closable="false"
      style="margin-bottom: 16px"
    />

    <ElScrollbar height="60vh">
      <ElTree
        ref="treeRef"
        v-loading="loading"
        :data="treeData"
        node-key="id"
        show-checkbox
        default-expand-all
        :props="treeProps"
        @check="handleCheck"
      />
    </ElScrollbar>

    <template #footer>
      <ElButton @click="visible = false">取消</ElButton>
      <ElButton
        type="primary"
        :loading="submitting"
        :disabled="isProtectedUser"
        @click="handleSubmit"
      >
        保存
      </ElButton>
    </template>
  </ElDialog>
</template>

<script setup lang="ts">
  import type { ElTree } from 'element-plus'
  import { ElMessage } from 'element-plus'
  import { fetchGetUserPermission, fetchSaveUserPermission } from '@/api/system-manage'

  type PermissionTreeNode = Api.Backend.AuthTreeNode & {
    disabled?: boolean
    children?: PermissionTreeNode[]
  }

  interface Props {
    modelValue: boolean
    userData?: Partial<Api.SystemManage.UserListItem>
  }

  interface Emits {
    (e: 'update:modelValue', value: boolean): void
  }

  const PROTECTED_USER_MESSAGE = '管理员账号默认拥有全部权限，不支持维护'

  const props = withDefaults(defineProps<Props>(), {
    modelValue: false,
    userData: undefined
  })

  const emit = defineEmits<Emits>()

  const treeRef = ref<InstanceType<typeof ElTree>>()
  const loading = ref(false)
  const submitting = ref(false)
  const treeData = ref<PermissionTreeNode[]>([])
  const checkedKeys = ref<Array<number | string>>([])

  const treeProps = {
    label: 'title',
    children: 'children',
    disabled: 'disabled'
  }

  const visible = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value)
  })

  // 管理员权限由后端兜底为全量权限，前端只读展示，避免误保存成部分权限。
  const isProtectedUser = computed(
    () =>
      Number(props.userData?.is_admin || 0) === 1 ||
      String(props.userData?.username || '').toLowerCase() === 'admin'
  )

  // 受保护用户仍展示权限树，但禁用所有节点，表达“拥有全部权限且不可维护”。
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

  const loadPermissionTree = async () => {
    if (!props.userData?.id) {
      treeData.value = []
      return
    }

    loading.value = true
    try {
      const data = await fetchGetUserPermission(props.userData.id)
      treeData.value = setTreeDisabled(
        Array.isArray(data.authNode) ? data.authNode : [],
        isProtectedUser.value
      )

      await nextTick()
      // 后端返回的是适合 Element Plus 回显的最深层 checked keys。
      treeRef.value?.setCheckedKeys(Array.isArray(data.checked) ? data.checked : [], true)
      syncCheckedKeys()
    } finally {
      loading.value = false
    }
  }

  const handleCheck = () => {
    syncCheckedKeys()
  }

  const handleSubmit = async () => {
    if (!props.userData?.id) {
      return
    }

    if (isProtectedUser.value) {
      ElMessage.warning(PROTECTED_USER_MESSAGE)
      return
    }

    submitting.value = true
    try {
      await fetchSaveUserPermission(props.userData.id, checkedKeys.value)
      visible.value = false
    } finally {
      submitting.value = false
    }
  }

  const handleClosed = () => {
    treeRef.value?.setCheckedKeys([], true)
    checkedKeys.value = []
  }

  watch(
    () => props.modelValue,
    (value) => {
      if (value) {
        loadPermissionTree()
      }
    }
  )
</script>
