<template>
  <div class="menu-page art-full-height">
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
        :showZebra="false"
        v-model:columns="columnChecks"
        @refresh="getMenuList"
      >
        <template #left>
          <ElButton v-auth="'system:menu:save'" @click="openCreateDialog()" v-ripple>
            新增菜单
          </ElButton>
          <ElButton @click="toggleExpand" v-ripple>
            {{ expanded ? '收起' : '展开' }}
          </ElButton>
        </template>
      </ArtTableHeader>

      <ArtTable
        ref="tableRef"
        rowKey="id"
        :loading="loading"
        :columns="columns"
        :data="filteredTableData"
        :stripe="false"
        :tree-props="{ children: 'children', hasChildren: 'hasChildren' }"
      />
    </ElCard>

    <MenuDialog
      v-model="dialogVisible"
      :edit-data="currentMenuData"
      :tree-data="menuTreeOptions"
      @success="getMenuList"
    />
  </div>
</template>

<script setup lang="ts">
  import ArtButtonTable from '@/components/core/forms/art-button-table/index.vue'
  import { useAuth } from '@/hooks'
  import { useTableColumns } from '@/hooks/core/useTableColumns'
  import { fetchDeleteMenu, fetchGetMenuList } from '@/api/system-manage'
  import MenuDialog from './modules/menu-dialog.vue'
  import { ElMessageBox, ElTag } from 'element-plus'
  import { useWindowSize } from '@vueuse/core'

  defineOptions({ name: 'Menus' })

  type MenuListItem = Api.SystemManage.MenuListItem
  type MenuTypeText = Exclude<MenuListItem['type_text'], undefined>

  const { hasAuth } = useAuth()
  const loading = ref(false)
  const expanded = ref(false)
  const tableRef = ref()
  const dialogVisible = ref(false)
  const currentMenuData = ref<Partial<MenuListItem> | null>(null)
  const { width } = useWindowSize()
  const isCompactTable = computed(() => width.value <= 1180)

  const filters = reactive({
    title: '',
    path: ''
  })

  const appliedFilters = reactive({
    title: '',
    path: ''
  })

  const tableData = ref<MenuListItem[]>([])

  const searchItems = computed(() => [
    {
      label: '菜单名称',
      key: 'title',
      type: 'input',
      props: {
        clearable: true,
        placeholder: '请输入菜单名称'
      }
    },
    {
      label: '路由地址',
      key: 'path',
      type: 'input',
      props: {
        clearable: true,
        placeholder: '请输入路由地址'
      }
    }
  ])

  const menuTreeOptions = computed<MenuListItem[]>(() => [
    {
      id: 0,
      pid: 0,
      title: '顶级菜单',
      path: '',
      component: '',
      hidden: 0,
      type: 0,
      children: tableData.value
    }
  ])

  const getMenuTypeTag = (row: MenuListItem) => {
    if (Number(row.type) === 2) return 'danger'
    if (Number(row.type) === 0) return 'info'
    return 'primary'
  }

  const getDictTagType = (color?: string) => {
    switch (String(color || '').toLowerCase()) {
      case 'green':
        return 'success'
      case 'blue':
        return 'primary'
      case 'red':
        return 'danger'
      case 'yellow':
      case 'orange':
        return 'warning'
      case 'gray':
      case 'grey':
        return 'info'
      default:
        return ''
    }
  }

  const getMenuTypeLabel = (typeText?: MenuTypeText) => {
    if (!typeText) {
      return '-'
    }

    if (typeof typeText === 'string') {
      return typeText || '-'
    }

    return typeText.name || '-'
  }

  const resolveMenuTypeTag = (row: MenuListItem) => {
    if (row.type_text && typeof row.type_text !== 'string') {
      return getDictTagType(row.type_text.color) || getMenuTypeTag(row)
    }

    return getMenuTypeTag(row)
  }

  const getStatusTag = (row: MenuListItem) => {
    return Number(row.hidden) === 1 ? 'warning' : 'success'
  }

  const { columnChecks, columns, updateColumn } = useTableColumns(() => [
    {
      prop: 'title',
      label: '菜单名称',
      minWidth: 180
    },
    {
      prop: 'type_text',
      label: '类型',
      width: 100,
      formatter: (row: MenuListItem) =>
        h(ElTag, { type: resolveMenuTypeTag(row) }, () => getMenuTypeLabel(row.type_text))
    },
    {
      prop: 'path',
      label: '路由地址',
      minWidth: 160,
      formatter: (row: MenuListItem) => row.path || '-'
    },
    {
      prop: 'component',
      label: '路由组件',
      minWidth: 180,
      formatter: (row: MenuListItem) => row.component || '-'
    },
    {
      prop: 'rules',
      label: '权限节点',
      minWidth: 160,
      formatter: (row: MenuListItem) => row.rules || '-'
    },
    {
      prop: 'status',
      label: '显示状态',
      width: 100,
      formatter: (row: MenuListItem) =>
        h(ElTag, { type: getStatusTag(row) }, () => row.status || '-')
    },
    {
      prop: 'sort',
      label: '排序',
      width: 80,
      formatter: (row: MenuListItem) => row.sort || 0
    },
    {
      prop: 'operation',
      label: '操作',
      width: 180,
      fixed: isCompactTable.value ? undefined : 'right',
      formatter: (row: MenuListItem) => {
        const buttons = []

        if (Number(row.type) !== 2 && hasAuth('system:menu:save')) {
          buttons.push(
            h(ArtButtonTable, {
              type: 'add',
              tooltip: '新增下级',
              onClick: () => openCreateDialog(row)
            })
          )
        }

        if (hasAuth('system:menu:update')) {
          buttons.push(
            h(ArtButtonTable, {
              type: 'edit',
              onClick: () => openEditDialog(row)
            })
          )
        }

        if (hasAuth('system:menu:delete')) {
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

  watch(
    isCompactTable,
    (compact) => {
      updateColumn('operation', { fixed: compact ? undefined : 'right' })
    },
    { immediate: true }
  )

  const getMenuList = async () => {
    loading.value = true
    try {
      tableData.value = await fetchGetMenuList()
    } finally {
      loading.value = false
    }
  }

  const searchTree = (list: MenuListItem[]): MenuListItem[] => {
    const titleKeyword = appliedFilters.title.trim().toLowerCase()
    const pathKeyword = appliedFilters.path.trim().toLowerCase()

    if (!titleKeyword && !pathKeyword) {
      return list
    }

    return list
      .map((item) => {
        const children = item.children?.length ? searchTree(item.children) : []
        const titleMatch = !titleKeyword || item.title.toLowerCase().includes(titleKeyword)
        const pathMatch = !pathKeyword || (item.path || '').toLowerCase().includes(pathKeyword)

        if (titleMatch && pathMatch) {
          return {
            ...item,
            children: item.children
          }
        }

        if (children.length) {
          return {
            ...item,
            children
          }
        }

        return null
      })
      .filter(Boolean) as MenuListItem[]
  }

  const filteredTableData = computed(() => searchTree(tableData.value))

  const openCreateDialog = (parent?: MenuListItem) => {
    currentMenuData.value = {
      pid: parent?.id ?? 0,
      type: parent && Number(parent.type) === 2 ? 2 : 0
    }
    dialogVisible.value = true
  }

  const openEditDialog = (row: MenuListItem) => {
    currentMenuData.value = { ...row }
    dialogVisible.value = true
  }

  const handleDelete = async (row: MenuListItem) => {
    await ElMessageBox.confirm(`确定删除菜单“${row.title}”吗？`, '删除确认', {
      type: 'warning',
      confirmButtonText: '确定',
      cancelButtonText: '取消'
    })

    await fetchDeleteMenu(row.id)
    await getMenuList()
  }

  const handleSearch = () => {
    Object.assign(appliedFilters, filters)
  }

  const handleReset = async () => {
    Object.assign(filters, { title: '', path: '' })
    Object.assign(appliedFilters, { title: '', path: '' })
    await getMenuList()
  }

  const toggleExpand = () => {
    expanded.value = !expanded.value
    nextTick(() => {
      const table = tableRef.value?.elTableRef
      if (!table) {
        return
      }

      const toggleRows = (rows: MenuListItem[]) => {
        rows.forEach((row) => {
          if (row.children?.length) {
            table.toggleRowExpansion(row, expanded.value)
            toggleRows(row.children)
          }
        })
      }

      toggleRows(filteredTableData.value)
    })
  }

  onMounted(() => {
    getMenuList()
  })
</script>
