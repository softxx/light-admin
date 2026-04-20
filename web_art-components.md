# web_art 组件文档

这份文档用于记录 `web_art` 当前可复用的组件、hooks 和后台页面搭建方式，方便后续继续适配这个 ThinkPHP 后端项目时快速查阅。

路径说明：

- 下文所有路径都以“当前工程根目录”为基准
- 例如 `web_art/src/components` 表示“当前工程根目录下的 `web_art/src/components`”

适用目录：

- `web_art/src/components`
- `web_art/src/hooks/core`
- `web_art/src/views/system`

## 1. 当前项目里的常用搭建范式

`web_art` 在这个项目里，后台管理页基本可以按下面这个结构复用：

1. 接口层放在 `src/api/system-manage.ts`
2. 列表查询条件用 `ArtSearchBar`
3. 列表状态和分页用 `useTable`
4. 表格头工具栏用 `ArtTableHeader`
5. 数据表格用 `ArtTable`
6. 行内操作按钮用 `ArtButtonTable`
7. 新增/编辑表单放在独立弹窗组件里

典型参考页面：

- 用户管理：`web_art/src/views/system/user`
- 角色管理：`web_art/src/views/system/role`
- 菜单管理：`web_art/src/views/system/menu`
- 部门管理：`web_art/src/views/system/department`
- 日志管理：`web_art/src/views/system/logs`

## 2. 组件目录总览

`src/components` 当前主要分为两层：

- `business`
  - 面向当前项目业务的组件
- `core`
  - 通用基础组件，偏框架能力

当前高频目录如下：

- `src/components/business/backend-image-upload`
- `src/components/core/forms`
- `src/components/core/tables`
- `src/components/core/layouts`
- `src/components/core/views`
- `src/components/core/media`
- `src/components/core/charts`
- `src/components/core/cards`

## 3. 最重要的通用组件

### 3.1 ArtTable

文件：

- `web_art/src/components/core/tables/art-table/index.vue`

定位：

- 对 `Element Plus` 的 `ElTable` 做了一层后台项目封装
- 统一处理列配置、插槽、格式化、展开列、索引列、分页显示

常用能力：

- 接收 `columns` 列配置
- 接收 `data` 表格数据
- 接收 `pagination` 分页信息
- 接收 `loading`
- 支持普通列、索引列、展开列
- 支持列 `slot` 和自定义 `formatter`

分页事件：

- `pagination:size-change`
- `pagination:current-change`

适用场景：

- 几乎所有后台列表页都应该优先复用它，而不是直接手写 `ElTable`

### 3.2 ArtTableHeader

文件：

- `web_art/src/components/core/tables/art-table-header/index.vue`

定位：

- 列表页表格上方的工具栏组件

常用能力：

- 搜索栏显示/隐藏
- 刷新
- 尺寸切换
- 全屏
- 列显示控制
- 斑马纹、边框、表头背景开关

适用场景：

- 凡是有“搜索区 + 表格区”的管理页，都建议配合 `ArtTable` 一起使用

### 3.3 ArtSearchBar

文件：

- `web_art/src/components/core/forms/art-search-bar/index.vue`

定位：

- 动态搜索表单生成器

表单项配置方式：

- 通过 `items` 数组声明字段
- 通过 `v-model` 绑定查询参数对象
- 触发 `search` 和 `reset` 事件

已支持的常见类型：

- `input`
- `number`
- `select`
- `switch`
- `checkbox`
- `checkboxgroup`
- `radiogroup`
- `date`
- `daterange`
- `datetime`
- `datetimerange`
- `rate`
- `slider`
- `cascader`
- `timepicker`
- `timeselect`
- `treeselect`

适用场景：

- 用户列表、角色列表、日志列表等查询页

建议：

- 后续新增页面优先用它生成搜索区，不要每页单独重写一套 `ElForm`

### 3.4 ArtButtonTable

文件：

- `web_art/src/components/core/forms/art-button-table/index.vue`

定位：

- 列表操作列的小型图标按钮组件

当前已经适配的点：

- 支持悬浮提示文字
- 标准按钮支持 `add`、`edit`、`delete`、`view`、`more`
- 已经压缩按钮尺寸，避免 3 个操作按钮换行

适用场景：

- 表格行内操作列统一使用它，避免每页都手写图标按钮样式

### 3.5 BackendImageUpload

文件：

- `web_art/src/components/business/backend-image-upload/index.vue`

定位：

- 当前项目专门为 ThinkPHP 后端适配的图片上传组件

常用能力：

- 单图上传
- 预览
- 重新上传
- 清空图片
- 限制文件大小
- 限制必须是图片

接口对接：

- 内部调用 `fetchUploadImage`
- 现在已经接到后端图片上传接口

当前使用位置：

- 用户资料头像
- 用户编辑弹窗里也可以直接复用

建议：

- 以后后台凡是上传头像、封面、缩略图，优先用它，不要再单独封装一套上传逻辑

### 3.6 ArtWangEditor

文件：

- `web_art/src/components/core/forms/art-wang-editor/index.vue`

定位：

- 基于 `wangeditor` 的富文本组件封装

常用能力：

- `v-model` 绑定 HTML 内容
- 工具栏裁剪
- 自定义上传配置
- 内置图片上传

当前后端适配情况：

- 默认上传地址已经改为当前后端图片上传接口
- 上传成功后会自动回填图片地址

适用场景：

- 公告内容
- 富文本描述
- 文章类后台表单

## 4. 常用 hooks

### 4.1 useTable

文件：

- `web_art/src/hooks/core/useTable.ts`

定位：

- 列表页核心 hook
- 当前后台页面最重要的状态管理入口之一

主要职责：

- 发起列表接口请求
- 维护 `data`
- 维护 `loading`
- 维护 `pagination`
- 维护 `searchParams`
- 统一处理查询、翻页、刷新、重置
- 可选缓存
- 可选列配置联动

常用返回值：

- `data`
- `loading`
- `pagination`
- `searchParams`
- `getData`
- `resetSearchParams`
- `handleSizeChange`
- `handleCurrentChange`
- `refreshData`
- `refreshCreate`
- `refreshUpdate`
- `refreshRemove`

当前项目里的重要约定：

- 已经统一兼容 ThinkPHP 后端分页参数
- 现在应优先使用 `page` 和 `pageSize`
- 即使某些旧页面仍传 `current`、`size`，hook 里也做了兼容转换

建议：

- 后面新增分页页面，优先用 `useTable`，不要自己重复维护分页状态

### 4.2 useTableColumns

文件：

- `web_art/src/hooks/core/useTableColumns.ts`

定位：

- 动态列配置管理

常用能力：

- 列显示/隐藏
- 列顺序调整
- 动态增加列
- 动态删除列
- 更新列配置

适用场景：

- 配合 `ArtTableHeader` 做“列显示控制”

### 4.3 useAuth

文件：

- `web_art/src/hooks/core/useAuth.ts`

定位：

- 页面按钮权限判断

当前逻辑：

- 优先读用户信息里的 `buttons`
- 兼容读取 `rules`
- 后台模式下也兼容路由 `meta.authList`

适用场景：

- 新增、编辑、删除、导出等按钮按权限显隐

建议：

- 后续新页面的按钮权限控制，统一走 `hasAuth`

### 4.4 其他可关注 hooks

文件目录：

- `web_art/src/hooks/core`

常见辅助 hooks：

- `useTheme.ts`
- `useChart.ts`
- `useLayoutHeight.ts`
- `useTableHeight.ts`
- `useHeaderBar.ts`

这些更偏界面层和布局层，后台 CRUD 页面最常用的还是 `useTable` 和 `useAuth`。

## 5. 目前已适配到 ThinkPHP 后端的几个关键约定

### 5.1 分页参数

后端实际识别：

- `page`
- `pageSize`

因此后续新增列表接口时，前端要优先遵守这个规范。

已经处理好的兼容层位置：

- `web_art/src/utils/table/tableConfig.ts`
- `web_art/src/utils/table/tableUtils.ts`
- `web_art/src/hooks/core/useTable.ts`

### 5.2 列表页接口组织方式

建议继续沿用当前做法：

- 所有系统管理类接口集中在 `src/api/system-manage.ts`
- 一个页面尽量只依赖一个明确的接口模块

这样后面改接口、排查联调问题会更快。

### 5.3 上传能力

当前上传能力已经适配：

- 单图上传组件 `BackendImageUpload`
- 富文本图片上传 `ArtWangEditor`

建议后续不要再各页面单独写上传请求，统一复用现有封装。

### 5.4 权限与菜单

当前项目已经做过 ThinkPHP 后端菜单兼容，尤其包括：

- `Layout`
- `RouteView`
- 外链
- `iframe`
- 隐藏路由

相关核心位置：

- `web_art/src/router/core/MenuProcessor.ts`
- `web_art/src/api/auth.ts`

后续如果再新增后台菜单类型，优先从这两个位置看。

## 6. 推荐的后台页面开发模板

如果后续继续新增后台页面，建议按这个顺序搭：

1. 在 `src/api` 里定义接口
2. 在页面里用 `useTable` 管数据、分页、刷新
3. 用 `ArtSearchBar` 组搜索区
4. 用 `ArtTableHeader` 组工具栏
5. 用 `ArtTable` 渲染列表
6. 用 `ArtButtonTable` 统一操作列
7. 新增/编辑表单拆成独立弹窗组件

这个范式已经在以下页面验证过：

- 用户管理
- 角色管理
- 菜单管理
- 登录日志
- 操作日志
- 代码生成器

## 7. 我后续查看组件时建议优先顺序

如果是为了继续做这个项目的后端适配，我后续优先看这几类文件：

1. `src/views/system/*`
2. `src/api/system-manage.ts`
3. `src/hooks/core/useTable.ts`
4. `src/components/core/tables/*`
5. `src/components/core/forms/*`
6. `src/components/business/*`

也就是说，后续优先基于现有页面复制和抽取，不建议重新发明一套页面结构。

## 8. 一个简化判断原则

后续如果我在适配某个新后台页面时拿不准该复用什么组件，可以先按下面的经验判断：

- 只要是“搜索 + 列表 + 分页”，先看 `ArtSearchBar + useTable + ArtTable`
- 只要是“表格行内操作”，先看 `ArtButtonTable`
- 只要是“图片上传”，先看 `BackendImageUpload`
- 只要是“富文本”，先看 `ArtWangEditor`
- 只要是“按钮权限控制”，先看 `useAuth`

按这个规则，基本可以覆盖当前后台管理项目的大部分页面。
