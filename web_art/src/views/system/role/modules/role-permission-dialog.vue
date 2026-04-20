<template>
  <ElDialog v-model="visible" title="角色权限" width="560px" align-center @closed="handleClosed">
    <ElAlert
      v-if="isProtectedRole"
      title="超级管理员角色默认拥有全部权限，不支持维护。"
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
        :disabled="isProtectedRole"
        @click="handleSubmit"
        >保存</ElButton
      >
    </template>
  </ElDialog>
</template>

<script setup lang="ts">
  import type { ElTree } from 'element-plus'
  import { ElMessage } from 'element-plus'
  import { fetchGetRolePermission, fetchSaveRolePermission } from '@/api/system-manage'

  type PermissionTreeNode = Api.Backend.AuthTreeNode & {
    disabled?: boolean
    children?: PermissionTreeNode[]
  }

  interface Props {
    modelValue: boolean
    roleData?: Api.SystemManage.RoleListItem
  }

  interface Emits {
    (e: 'update:modelValue', value: boolean): void
    (e: 'success'): void
  }

  const SUPER_ADMIN_ROLE_ID = '1'
  const PROTECTED_ROLE_MESSAGE = '超级管理员角色默认拥有全部权限，不支持维护'

  const props = withDefaults(defineProps<Props>(), {
    modelValue: false,
    roleData: undefined
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

  const isProtectedRole = computed(() => String(props.roleData?.id ?? '') === SUPER_ADMIN_ROLE_ID)

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
    checkedKeys.value = (treeRef.value?.getCheckedKeys(true) as Array<number | string>) || []
  }

  const loadPermissionTree = async () => {
    if (!props.roleData?.id) {
      treeData.value = []
      return
    }

    loading.value = true
    try {
      const data = await fetchGetRolePermission(props.roleData.id)
      treeData.value = setTreeDisabled(
        Array.isArray(data.authNode) ? data.authNode : [],
        isProtectedRole.value
      )

      await nextTick()
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
    if (!props.roleData?.id) {
      return
    }

    if (isProtectedRole.value) {
      ElMessage.warning(PROTECTED_ROLE_MESSAGE)
      return
    }

    submitting.value = true
    try {
      await fetchSaveRolePermission(props.roleData.id, checkedKeys.value)
      visible.value = false
      emit('success')
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
