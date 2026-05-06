# 公共表格过滤能力说明

## 1. 功能概述

本次改造将原本只存在于“操作日志”的快捷过滤 / 高级过滤能力，抽离为一套可复用的公共能力，并接入到了以下分页表格页面：

- 操作日志
- 登录日志
- 管理员管理
- 角色管理

当前不包含树表格页面，树表格后续如需接入，建议按页面特点单独评估，只复用过滤协议和部分 UI 能力，不强行统一整套交互。

## 2. 设计目标

本次实现遵循以下目标：

- 不做一个耦合很重的“万能大组件”
- 将公共能力拆成协议层、共享能力层、页面适配层
- 保留各业务页字段差异，统一过滤协议和交互方式
- 保留老接口的兼容能力，避免影响现有查询逻辑
- 后续新增列表页时，尽量只补字段配置和后端搜索映射

## 3. 整体分层

### 3.1 协议层

负责统一前端过滤模型、操作符、字段 schema、请求序列化格式。

核心文件：

- `web_art/src/types/common/table-filter.ts`
- `web_art/src/utils/table/filter.ts`

这一层定义了：

- 字段类型：`text`、`number`、`date`、`select`、`special-select`
- 操作符：`contains`、`not_contains`、`eq`、`neq`、`gt`、`lt`、`gte`、`lte`、`empty`、`not_empty`
- 前端表单模型：`TableFilterFormModel`
- 条件组模型：`TableFilterGroup`
- 请求序列化方法：`buildDynamicTableFilterParams`

前端提交到接口时，统一转成两个参数：

- `quick_filter`
- `filters`

它们都是 JSON 字符串。

### 3.2 共享能力层

负责提供可复用的过滤 UI 和通用处理逻辑。

核心目录：

- `web_art/src/components/business/table-filters/`

包含组件：

- `table-quick-filter.vue`
- `table-filter-builder.vue`
- `table-advanced-filter.vue`

能力说明：

- 快捷过滤：单条件快速筛选
- 高级过滤：支持分组组合条件
- 条件组关系：组内 `且`，组间 `或`
- 根据字段类型自动切换输入控件
- 根据字段 schema 自动限制操作符和取值方式

### 3.3 页面适配层

每个页面只负责两件事：

- 提供本页字段 schema
- 在请求发出前，将表单模型序列化成统一过滤参数

典型页面文件：

- `web_art/src/views/system/logs/operate-log/index.vue`
- `web_art/src/views/system/logs/login-log/index.vue`
- `web_art/src/views/system/user/index.vue`
- `web_art/src/views/system/role/index.vue`

典型字段 schema 工厂：

- `web_art/src/views/system/user/modules/user-filter-fields.ts`
- `web_art/src/views/system/role/modules/role-filter-fields.ts`

## 4. 后端实现

后端增加了一套公共动态过滤 trait，用来解析 `quick_filter` / `filters`。

核心文件：

- `app/model/system/search/DynamicFilterSearchTrait.php`

当前已接入的搜索 trait：

- `app/model/system/search/OperateLogSearch.php`
- `app/model/system/search/LoginLogSearch.php`
- `app/model/system/search/UserSearch.php`
- `app/model/system/search/RoleSearch.php`

### 4.1 trait 的职责

- 解析快捷过滤 JSON
- 解析高级过滤 JSON
- 根据字段配置校验字段和操作符是否合法
- 根据字段类型规范化值
- 将条件应用到 ThinkPHP 查询对象上
- 支持页面保留自定义 handler

### 4.2 字段配置格式

每个搜索 trait 通过 `getDynamicFilterFieldConfigs()` 提供字段配置，例如：

```php
protected function getDynamicFilterFieldConfigs(): array
{
    return [
        'name' => ['type' => 'text'],
        'status' => ['type' => 'select'],
        'create_time' => ['type' => 'date'],
        'dept_id' => [
            'type' => 'select',
            'operators' => ['eq', 'neq'],
            'handler' => 'applyDepartmentFilterCondition',
        ],
    ];
}
```

支持的常见配置项：

- `type`：字段类型
- `operators`：覆盖默认操作符
- `query_field`：实际查询字段名
- `handler`：自定义处理方法

### 4.3 自定义 handler 场景

以下情况推荐使用自定义 handler：

- 字段值需要先转换再查询
- 查询目标不在当前表字段上
- 需要先联表 / 查关联结果再过滤
- 需要特殊的等于 / 不等于逻辑

本次已实现的典型场景：

- 操作日志的 `user_id`
- 登录日志的 `realname`
- 管理员管理的账号、姓名、手机号、状态和创建时间筛选

## 5. 前端请求协议

### 5.1 快捷过滤

示例：

```json
{
  "field": "username",
  "operator": "contains",
  "value": "admin"
}
```

提交后：

```txt
quick_filter={"field":"username","operator":"contains","value":"admin"}
```

### 5.2 高级过滤

示例：

```json
[
  {
    "conditions": [
      { "field": "status", "operator": "eq", "value": 1 },
      { "field": "dept_id", "operator": "eq", "value": 3 }
    ]
  },
  {
    "conditions": [
      { "field": "username", "operator": "contains", "value": "test" }
    ]
  }
]
```

语义说明：

- 第一组：`status = 1` 且 `dept_id = 3`
- 第二组：`username 包含 test`
- 最终关系：`(status = 1 AND dept_id = 3) OR (username contains test)`

## 6. 已接入页面说明

### 6.1 操作日志

状态：

- 已完成公共模块替换
- 页面行为保持不变
- 原操作日志模块文件保留为兼容包装层

相关文件：

- `web_art/src/views/system/logs/operate-log/index.vue`
- `web_art/src/views/system/logs/operate-log/modules/operate-log-quick-filter.vue`
- `web_art/src/views/system/logs/operate-log/modules/operate-log-filter-builder.vue`
- `web_art/src/views/system/logs/operate-log/modules/operate-log-advanced-filter.vue`

### 6.2 登录日志

状态：

- 已接入公共快捷过滤 / 高级过滤
- 列表查询已走统一动态过滤协议
- 导出接口已带上当前过滤条件

相关文件：

- `web_art/src/views/system/logs/login-log/index.vue`
- `app/model/system/search/LoginLogSearch.php`

### 6.3 管理员管理

状态：

- 已接入公共快捷过滤 / 高级过滤
- 页面字段 schema 已抽离
- 支持账号名、姓名、手机号、状态、创建时间等过滤

相关文件：

- `web_art/src/views/system/user/index.vue`
- `web_art/src/views/system/user/modules/user-search.vue`
- `web_art/src/views/system/user/modules/user-filter-fields.ts`
- `app/model/system/search/UserSearch.php`

### 6.4 角色管理

状态：

- 已接入公共快捷过滤 / 高级过滤
- 页面字段 schema 已抽离
- 支持数据范围、创建时间等过滤

相关文件：

- `web_art/src/views/system/role/index.vue`
- `web_art/src/views/system/role/modules/role-search.vue`
- `web_art/src/views/system/role/modules/role-filter-fields.ts`
- `app/model/system/search/RoleSearch.php`

## 7. 新页面接入方式

后续如果要给新的分页表格页面接入这套能力，建议按以下步骤处理。

### 7.1 前端步骤

1. 定义本页字段 schema

建议新增一个单独文件，例如：

```txt
web_art/src/views/xxx/modules/xxx-filter-fields.ts
```

2. 搜索组件接入共享组件

使用：

- `TableQuickFilter`
- `TableAdvancedFilter`

3. 页面维护过滤表单模型

使用：

```ts
const searchForm = ref<TableFilterFormModel>(createTableFilterFormModel())
```

4. 页面发请求前做序列化

使用：

```ts
replaceSearchParams(buildDynamicTableFilterParams(searchForm.value, filterFields.value))
```

### 7.2 后端步骤

1. 为对应模型的搜索 trait 引入：

```php
use DynamicFilterSearchTrait;
```

2. 实现：

```php
protected function getDynamicFilterFieldConfigs(): array
```

3. 如果字段需要特殊处理，再补：

```php
protected function applyXxxFilterCondition($query, array $filter): void
```

4. 如需兼容老接口，保留原 `searchXxxAttr` 方法

## 8. 当前限制

当前版本的设计边界如下：

- 只覆盖分页表格页面
- 不强行接入树表格页面
- 不做跨页面统一字段字典中心
- 不处理特别复杂的多表组合 DSL

这套方案适合“后台常规列表页”的统一过滤，不适合把所有复杂查询都塞进一个协议里。

## 9. 推荐扩展方向

如果后续继续完善，建议优先考虑下面几个方向：

- 给更多分页列表页补字段 schema 和搜索 trait
- 给日志类页面继续统一导出、清空、删除交互
- 增加字段 schema 的公共字典复用
- 视需要增加“保存过滤方案”能力
- 视需要增加更细的日期范围 / 数值范围过滤

## 10. 验证结果

本次改造完成后，已执行以下验证：

- `php -l app/model/system/search/DynamicFilterSearchTrait.php`
- `php -l app/model/system/search/OperateLogSearch.php`
- `php -l app/model/system/search/LoginLogSearch.php`
- `php -l app/model/system/search/UserSearch.php`
- `php -l app/model/system/search/RoleSearch.php`
- `php -l app/model/system/LoginLog.php`
- `php -l app/model/system/Role.php`
- `php -l app/model/system/User.php`
- `pnpm build`（目录：`web_art`）

均已通过。
