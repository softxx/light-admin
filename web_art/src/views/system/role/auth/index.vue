<template>
  <div class="role-auth-page art-full-height">
    <ElCard v-loading="loading" class="role-auth-page__card">
      <template #header>
        <div class="role-auth-page__header">
          <div>
            <div class="role-auth-page__title">角色权限配置</div>
            <div class="role-auth-page__subtitle">
              {{ roleDetail?.name || '未找到角色' }}
              <span v-if="roleDetail?.role_key">({{ roleDetail.role_key }})</span>
            </div>
          </div>

          <div class="role-auth-page__actions">
            <ElButton @click="router.push('/system/role')">返回角色列表</ElButton>
            <ElButton
              type="primary"
              :loading="submitting"
              :disabled="isProtectedRole"
              @click="handleSubmit"
            >
              保存权限
            </ElButton>
          </div>
        </div>
      </template>

      <ElAlert
        title="这个页面用于兼容后台菜单里的隐藏授权路由，实际能力与角色列表里的“权限配置”一致。"
        type="info"
        :closable="false"
        style="margin-bottom: 16px"
      />

      <ElAlert
        v-if="isProtectedRole"
        title="超级管理员角色默认拥有全部权限，不支持维护。"
        type="info"
        :closable="false"
        style="margin-bottom: 16px"
      />

      <ElEmpty v-if="!treeData.length && !loading" description="暂无可配置的权限节点" />

      <ElScrollbar v-else height="calc(100vh - 300px)">
        <ElTree
          ref="treeRef"
          :data="treeData"
          node-key="id"
          show-checkbox
          default-expand-all
          :props="treeProps"
          @check="handleCheck"
        />
      </ElScrollbar>
    </ElCard>
  </div>
</template>

<script setup lang="ts">
  import type { ElTree } from 'element-plus'
  import { ElMessage } from 'element-plus'
  import {
    fetchGetRoleDetail,
    fetchGetRolePermission,
    fetchSaveRolePermission
  } from '@/api/system-manage'

  type PermissionTreeNode = Api.Backend.AuthTreeNode & {
    disabled?: boolean
    children?: PermissionTreeNode[]
  }

  defineOptions({ name: 'RoleAuth' })

  const SUPER_ADMIN_ROLE_ID = '1'
  const PROTECTED_ROLE_MESSAGE = '超级管理员角色默认拥有全部权限，不支持维护'

  const route = useRoute()
  const router = useRouter()

  const treeRef = ref<InstanceType<typeof ElTree>>()
  const loading = ref(false)
  const submitting = ref(false)
  const roleDetail = ref<Api.SystemManage.RoleListItem>()
  const treeData = ref<PermissionTreeNode[]>([])
  const checkedKeys = ref<Array<number | string>>([])

  const roleId = computed(() => String(route.query.id || route.params.id || ''))
  const isProtectedRole = computed(() => roleId.value === SUPER_ADMIN_ROLE_ID)

  const treeProps = {
    label: 'title',
    children: 'children',
    disabled: 'disabled'
  }

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

  const loadData = async () => {
    if (!roleId.value) {
      roleDetail.value = undefined
      treeData.value = []
      return
    }

    loading.value = true
    try {
      const [detail, permission] = await Promise.all([
        fetchGetRoleDetail(roleId.value),
        fetchGetRolePermission(roleId.value)
      ])

      roleDetail.value = detail
      treeData.value = setTreeDisabled(
        Array.isArray(permission.authNode) ? permission.authNode : [],
        isProtectedRole.value
      )

      await nextTick()
      treeRef.value?.setCheckedKeys(
        Array.isArray(permission.checked) ? permission.checked : [],
        true
      )
      syncCheckedKeys()
    } finally {
      loading.value = false
    }
  }

  const handleCheck = () => {
    syncCheckedKeys()
  }

  const handleSubmit = async () => {
    if (!roleId.value) {
      ElMessage.warning('缺少角色 ID，无法保存权限')
      return
    }

    if (isProtectedRole.value) {
      ElMessage.warning(PROTECTED_ROLE_MESSAGE)
      return
    }

    submitting.value = true
    try {
      await fetchSaveRolePermission(roleId.value, checkedKeys.value)
      ElMessage.success('角色权限已更新')
    } finally {
      submitting.value = false
    }
  }

  watch(
    roleId,
    () => {
      loadData()
    },
    { immediate: true }
  )
</script>

<style scoped lang="scss">
  .role-auth-page__card {
    height: 100%;
  }

  .role-auth-page__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
  }

  .role-auth-page__title {
    font-size: 16px;
    font-weight: 600;
    color: var(--el-text-color-primary);
  }

  .role-auth-page__subtitle {
    margin-top: 4px;
    color: var(--el-text-color-secondary);
    font-size: 13px;
  }

  .role-auth-page__actions {
    display: flex;
    gap: 12px;
  }

  @media (width <= 768px) {
    .role-auth-page__header {
      align-items: flex-start;
      flex-direction: column;
    }

    .role-auth-page__actions {
      width: 100%;
    }

    .role-auth-page__actions :deep(.el-button) {
      flex: 1;
    }
  }
</style>
