<template>
  <div class="art-full-height">
    <ArtSearchBar
      v-model="filters"
      :items="searchItems"
      :showExpand="false"
      @reset="handleReset"
      @search="handleSearch"
    />

    <ElCard class="art-table-card" style="margin-top: 12px">
      <ArtTableHeader
        :loading="loading"
        v-model:columns="columnChecks"
        @refresh="getDepartmentList"
      >
        <template #left>
          <ElButton v-auth="'system:department:save'" @click="openCreateDialog()" v-ripple>
            新增部门
          </ElButton>
        </template>
      </ArtTableHeader>

      <ArtTable
        :loading="loading"
        :data="filteredTableData"
        :columns="columns"
        rowKey="id"
        :stripe="false"
        :tree-props="{ children: 'children', hasChildren: 'hasChildren' }"
      />
    </ElCard>

    <DepartmentDialog
      v-model="dialogVisible"
      :edit-data="currentDepartment"
      :tree-data="departmentTreeOptions"
      @success="getDepartmentList"
    />
  </div>
</template>

<script setup lang="ts">
  import ArtButtonTable from '@/components/core/forms/art-button-table/index.vue'
  import { useAuth } from '@/hooks'
  import { useTableColumns } from '@/hooks/core/useTableColumns'
  import { fetchDeleteDepartment, fetchGetDepartmentList } from '@/api/system-manage'
  import DepartmentDialog from './modules/department-dialog.vue'
  import { ElMessageBox } from 'element-plus'

  defineOptions({ name: 'Department' })

  type DepartmentItem = Api.SystemManage.DepartmentOption & {
    sort?: number
    leader_id?: number | string
    leader_name?: string
    leader_user?: { id: number | string; realname: string } | null
  }

  const { hasAuth } = useAuth()

  const loading = ref(false)
  const dialogVisible = ref(false)
  const currentDepartment = ref<Record<string, any> | null>(null)
  const filters = reactive({ key: '' })
  const appliedFilters = reactive({ key: '' })
  const tableData = ref<DepartmentItem[]>([])

  const searchItems = computed(() => [
    {
      label: '部门名称',
      key: 'key',
      type: 'input',
      props: {
        clearable: true,
        placeholder: '请输入部门名称'
      }
    }
  ])

  const departmentTreeOptions = computed<DepartmentItem[]>(() => [
    {
      id: 0,
      value: 0,
      title: '顶级部门',
      name: '顶级部门',
      children: tableData.value
    }
  ])

  const { columnChecks, columns } = useTableColumns(() => [
    {
      prop: 'name',
      label: '部门名称',
      minWidth: 180
    },
    {
      prop: 'leader_name',
      label: '负责人',
      minWidth: 120,
      formatter: (row: DepartmentItem) => row.leader_name || row.leader_user?.realname || '-'
    },
    {
      prop: 'sort',
      label: '排序',
      width: 100,
      formatter: (row: DepartmentItem) => row.sort || 0
    },
    {
      prop: 'operation',
      label: '操作',
      width: 150,
      fixed: 'right',
      formatter: (row: DepartmentItem) => {
        const buttons = []

        if (hasAuth('system:department:save')) {
          buttons.push(
            h(ArtButtonTable, {
              type: 'add',
              tooltip: '新增下级',
              onClick: () => openCreateDialog(row)
            }),
            h(ArtButtonTable, {
              type: 'edit',
              onClick: () => openEditDialog(row)
            })
          )
        }

        if (hasAuth('system:department:delete')) {
          buttons.push(
            h(ArtButtonTable, {
              type: 'delete',
              onClick: () => handleDelete(row)
            })
          )
        }

        return h('div', buttons)
      }
    }
  ])

  const getDepartmentList = async () => {
    loading.value = true
    try {
      tableData.value = (await fetchGetDepartmentList()) as DepartmentItem[]
    } finally {
      loading.value = false
    }
  }

  const filterTree = (list: DepartmentItem[]): DepartmentItem[] => {
    const keyword = appliedFilters.key.trim().toLowerCase()
    if (!keyword) {
      return list
    }

    return list
      .map((item) => {
        const children = item.children?.length ? filterTree(item.children as DepartmentItem[]) : []
        const matched = item.name.toLowerCase().includes(keyword)

        if (matched || children.length) {
          return {
            ...item,
            children
          }
        }

        return null
      })
      .filter(Boolean) as DepartmentItem[]
  }

  const filteredTableData = computed(() => filterTree(tableData.value))

  const openCreateDialog = (parent?: DepartmentItem) => {
    currentDepartment.value = {
      parent_id: parent?.id ?? 0
    }
    dialogVisible.value = true
  }

  const openEditDialog = (row: DepartmentItem) => {
    currentDepartment.value = row
    dialogVisible.value = true
  }

  const handleDelete = async (row: DepartmentItem) => {
    await ElMessageBox.confirm(`确定删除部门“${row.name}”吗？`, '删除确认', {
      type: 'warning',
      confirmButtonText: '确定',
      cancelButtonText: '取消'
    })

    await fetchDeleteDepartment(row.id)
    await getDepartmentList()
  }

  const handleSearch = () => {
    Object.assign(appliedFilters, filters)
  }

  const handleReset = async () => {
    Object.assign(filters, { key: '' })
    Object.assign(appliedFilters, { key: '' })
    await getDepartmentList()
  }

  onMounted(() => {
    getDepartmentList()
  })
</script>
