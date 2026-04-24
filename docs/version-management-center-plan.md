# 版本统一管理中心方案

## 结论

Light Admin 可以实现后台检查版本和远程升级。结合当前项目形态，建议采用“可切换发布源 + 固定格式升级包 + 单机升级任务”的方案，而不是后台直接执行 `git pull`、`composer update`、`pnpm build` 这类现场构建命令。

当前项目是 ThinkPHP 8 后端 + Vue 3/Vite 前端，且只会单机部署，没有集群滚动发布诉求，所以升级流程可以走维护模式、备份、替换、迁移、清缓存、回滚这一条线。前端已有 `VITE_VERSION` 和本地版本提示逻辑，但它只适合处理浏览器缓存和前端通知，不等于服务端版本检查或远程升级。

## 推荐方案

推荐做一个后台模块：系统管理 / 版本管理中心。

它包含两部分：

1. 中心端发布源
   - 默认可以直接使用 GitHub Releases。
   - 后续如果切到 GitLab 或 Gitee，可以切换发行版平台适配器。
   - 每个版本按固定命名上传构建好的 zip 包，例如 `light-admin-1.0.1.zip`。

2. 项目内版本管理模块
   - 后台读取本机当前版本。
   - 请求配置的发布源，判断是否有新版本。
   - 显示版本号、发布时间、更新内容、是否强制升级、是否包含数据库迁移。
   - 超级管理员确认后下载升级包。
   - 校验包完整性。
   - 执行升级任务，记录进度和日志。
   - 失败时回滚代码和数据库备份。

## 为什么适合单机部署

单机部署的优势是状态简单，不需要处理多节点一致性、灰度流量、滚动升级和版本漂移。升级时可以短暂进入维护模式，保护文件替换和数据库迁移过程。

需要控制的风险主要是：

- Web 进程超时或权限不足。
- 文件覆盖到一半失败。
- 数据库迁移失败后无法回退。
- 升级包来源不可信。
- 前端静态资源缓存导致用户仍访问旧页面。

所以建议把真正升级动作做成 ThinkPHP 命令行任务，再由后台触发或提示管理员执行。

## 版本信息设计

建议新增一个根目录 `VERSION` 文件或 `config/version.php`：

```php
<?php

return [
    'version' => '1.0.0',
    'build' => '20260424.1',
    'commit' => '',
    'released_at' => '2026-04-24 00:00:00',
];
```

前端构建时继续使用 `web_art/.env` 里的 `VITE_VERSION`，但发布流程要保证它和后端版本一致。更理想的做法是构建脚本统一读取根目录 `VERSION`，同时写入前端环境变量和后端配置。

后台版本接口返回：

```json
{
  "current": {
    "version": "1.0.0",
    "build": "20260424.1",
    "commit": "abc1234"
  },
  "latest": {
    "version": "1.0.1",
    "build": "20260424.2",
    "required": false,
    "release_notes": "修复登录日志筛选问题"
  },
  "upgrade_available": true
}
```

## 发布源约定

当前支持四种发行版平台：

- `github`：读取 GitHub Releases API，适合直接用 GitHub 发行版。
- `gitlab`：读取 GitLab Releases API，适合 GitLab 项目发行版。
- `gitee`：读取 Gitee Releases API，适合 Gitee 仓库发行版。
- `cnb`：读取腾讯 CNB Open API，适合 CNB 仓库发行版。

### GitHub Release

推荐 Release tag 使用 `v1.0.1` 或 `1.0.1`，升级包资源名和后台配置的 `ASSET_PATTERN` 保持一致：

```text
tag: v1.0.1
asset: light-admin-1.0.1.zip
```

如需提供升级条件，可在 Release body 中增加隐藏元信息：

```markdown
<!-- upgrade
min_upgradable_version=1.0.0
php=>=8.2
database_migration=true
required=false
-->
```

GitHub API 如果返回 asset `digest`，后台会直接用它校验 sha256；否则建议额外上传同名 `.sha256` 文件，例如 `light-admin-1.0.1.zip.sha256`。

GitLab/Gitee/CNB 的规则保持一致：读取最新 Release，匹配 assets/links 中符合 `ASSET_PATTERN` 的 zip 包，再下载校验。CNB Open API 需要配置 `RELEASE_TOKEN`，仓库路径使用 `PROJECT = 组织/仓库`。

## 升级包结构

建议升级包使用 zip：

```text
light-admin-1.0.1.zip
├── package.json           # 可选：记录包内版本信息
├── checksums.sha256
├── backend/
│   ├── app/
│   ├── config/
│   ├── core/
│   ├── public/
│   ├── vendor/              # 推荐生产包内带 vendor，避免服务器上 composer update
│   └── VERSION
├── frontend/
│   └── dist/
├── migrations/
│   ├── 20260424_180000.sql
│   └── rollback_20260424_180000.sql
└── scripts/
    ├── precheck.php
    └── post_upgrade.php
```

生产升级包应由开发机或 CI 构建好：

- 后端执行 `composer install --no-dev --optimize-autoloader`。
- 前端执行 `pnpm build`。
- 把 `web_art/dist` 放入包内。
- 生成 `checksums.sha256`。
- 生成 sha256，必要时上传同名 `.sha256` 文件。

服务器只下载、校验、解压和替换，不在生产机临时拉代码或编译前端。

## 后台接口建议

新增后端模块：

- `app/adminapi/controller/system/Version.php`
- `app/service/system/VersionService.php`
- `app/service/system/UpgradeService.php`
- `app/model/system/Version.php`
- `app/model/system/UpgradeTask.php`
- `app/adminapi/route/system.php` 增加 `version` 路由组

接口建议：

| 接口 | 用途 |
| --- | --- |
| `POST /adminapi/version/current` | 获取当前版本、环境、最近升级记录 |
| `POST /adminapi/version/check` | 拉取发布源版本并比较版本 |
| `POST /adminapi/version/download` | 下载指定版本升级包 |
| `POST /adminapi/version/precheck` | 检查权限、磁盘、PHP 扩展、数据库连接、备份能力 |
| `POST /adminapi/version/upgrade` | 创建升级任务 |
| `POST /adminapi/version/rollback` | 回滚最近一次升级 |
| `POST /adminapi/version/task` | 查询升级任务进度和日志 |

所有接口只允许超级管理员访问，并要求二次确认。后续如果有短信、邮箱或 2FA，可以把升级确认接进去。

## 数据表建议

`light_system_versions`：

| 字段 | 说明 |
| --- | --- |
| `id` | 主键 |
| `version` | 版本号 |
| `build` | 构建号 |
| `commit_hash` | Git 提交 |
| `channel` | stable / beta |
| `release_notes` | 更新说明 JSON |
| `installed_at` | 安装时间 |
| `created_at` | 创建时间 |

`light_upgrade_tasks`：

| 字段 | 说明 |
| --- | --- |
| `id` | 主键 |
| `target_version` | 目标版本 |
| `package_path` | 本地升级包路径 |
| `backup_path` | 备份路径 |
| `status` | pending / downloading / verifying / backing_up / upgrading / success / failed / rolled_back |
| `progress` | 0-100 |
| `message` | 当前提示 |
| `logs` | 日志 JSON 或文本 |
| `error` | 失败原因 |
| `operator_id` | 操作人 |
| `started_at` | 开始时间 |
| `finished_at` | 结束时间 |
| `created_at` | 创建时间 |
| `updated_at` | 更新时间 |

## 升级流程

1. 检查版本
   - 读取本机 `VERSION`。
   - 请求配置的发布源。
   - 使用语义化版本比较，判断是否可升级。

2. 预检查
   - PHP 版本、扩展、磁盘空间。
   - `runtime/upgrade` 是否可写。
   - 目标目录是否可写。
   - 数据库连接是否正常。
   - 是否已存在升级锁。
   - 当前版本是否满足 `min_upgradable_version`。

3. 下载升级包
   - 下载到 `runtime/upgrade/packages`。
   - 校验 GitHub asset digest 或同名 `.sha256` 文件。

4. 备份
   - 备份当前代码关键目录。
   - 备份数据库，至少导出涉及迁移的表。
   - 记录备份路径。

5. 进入维护模式
   - 写入 `runtime/maintenance.lock`。
   - 后台和 API 返回维护提示，超级管理员可绕过或只允许查询升级进度。

6. 执行升级
   - 解压到临时目录。
   - 执行包内 `precheck.php`。
   - 替换后端文件和前端静态资源。
   - 执行 SQL 迁移。
   - 执行 `post_upgrade.php`。
   - 清理 runtime 缓存。
   - 写入新版本号。

7. 退出维护模式
   - 删除维护锁。
   - 记录成功版本。
   - 前端提示刷新并清理本地版本缓存。

8. 失败回滚
   - 停止后续步骤。
   - 恢复文件备份。
   - 执行 rollback SQL 或恢复数据库备份。
   - 删除维护锁。
   - 记录失败日志。

## 执行方式建议

第一阶段推荐：后台检查 + 下载 + 预检查 + 生成升级命令。

后台按钮最终提示：

```bash
php think system:upgrade runtime/upgrade/packages/light-admin-1.0.1.zip
```

这样最稳，因为升级过程不依赖 HTTP 请求生命周期，也不容易被 Nginx/PHP-FPM 超时中断。

第二阶段再做一键升级：

- 后台点击升级后创建 `upgrade_task`。
- 服务端用 `proc_open` 或计划任务启动 `php think system:upgrade --task=xxx`。
- 前端轮询任务状态。
- 如果服务器禁用了 `proc_open`，仍回退到手动执行命令。

不建议一开始就把所有升级动作放在普通 Controller 方法里同步执行。

## 安全策略

远程升级本质上等于远程写代码，安全边界要收紧：

- 只允许超级管理员。
- 升级操作要求重新输入密码。
- 发布源限定为配置的白名单域名，GitHub 模式会内置允许 GitHub 下载域名。
- 强制 HTTPS。
- 升级包必须 sha256 校验。
- 不执行包内任意 shell 命令，只允许 PHP 预检/后置脚本，且脚本能力要受控。
- 升级目录固定在 `runtime/upgrade`。
- 解压时禁止路径穿越，例如 `../` 和绝对路径。
- 写入升级锁，防止并发升级。
- 所有操作写入操作日志。
- `.env`、上传目录、运行时目录不被升级包覆盖。

## 前端页面设计

新增页面：系统管理 / 版本管理。

页面分区：

- 当前版本：版本号、构建号、发布时间、PHP 版本、数据库版本、部署路径。
- 最新版本：版本号、更新说明、发布时间、是否强制、是否需要数据库迁移。
- 操作区：检查更新、下载升级包、预检查、执行升级、查看日志、回滚。
- 历史记录：版本、操作人、状态、耗时、错误摘要。

现有的缓存管理页可以保留。版本升级成功后，可以复用现有浏览器缓存清理逻辑，提示用户刷新或重新登录。

## 与当前项目的落点

当前代码里已有这些基础：

- `web_art/.env` 已有 `VITE_VERSION = 1.0.0`。
- `web_art/src/utils/sys/upgrade.ts` 已有前端本地版本升级提示。
- `app/adminapi/route/system.php` 已有系统管理路由组。
- `app/adminapi/controller/system/Cache.php` 和 `app/service/system/CacheService.php` 已有超级管理员专用系统工具模块，可作为版本中心接口风格参考。
- `config/console.php` 已预留命令注册位置，适合新增 `system:upgrade`。

建议新增：

```text
config/version.php
app/adminapi/controller/system/Version.php
app/service/system/VersionService.php
app/service/system/UpgradeService.php
app/model/system/SystemVersion.php
app/model/system/UpgradeTask.php
app/command/SystemUpgrade.php
web_art/src/views/system/version-manage/index.vue
```

## 分阶段实施

### 第一阶段：版本检查中心

目标：能在后台看到当前版本和最新版本。

- 增加 `config/version.php`。
- 增加发布源配置。
- 增加 `version/current` 和 `version/check` 接口。
- 增加后台版本管理页面。
- 增加版本菜单和权限。

### 第二阶段：升级包下载和预检查

目标：能下载升级包，并判断本机是否具备升级条件。

- 下载升级包到 `runtime/upgrade/packages`。
- 校验 sha256。
- 增加写权限、磁盘、PHP 版本、数据库连接检查。
- 记录升级任务。

### 第三阶段：CLI 升级命令

目标：管理员可以执行一条命令完成升级。

- 新增 `php think system:upgrade {package}`。
- 实现备份、维护模式、替换文件、SQL 迁移、清缓存、记录版本。
- 实现失败回滚。

### 第四阶段：后台一键升级

目标：后台点击即可升级。

- Controller 创建任务。
- 后台触发 CLI 子进程。
- 前端轮询任务状态。
- 展示实时日志和结果。

## 最小可行版本

如果要快，建议 MVP 做到这里：

- 后台检查新版本。
- 后台下载并校验升级包。
- 后台给出升级命令。
- CLI 执行升级。
- 升级成功后后台显示历史记录。

这已经能覆盖“后台检查版本”和“远程升级”的主要诉求，同时风险可控。

后续服务器环境稳定后，再打开后台一键升级。

## 当前落地说明

本项目已经按第二阶段方案接入版本管理中心：

- 后台页面：`web_art/src/views/system/version-manage/index.vue`
- 后台接口：`/adminapi/version/current`、`check`、`download`、`precheck`、`upgrade`、`rollback`、`task`、`tasks`
- CLI 命令：`php think system:upgrade --task {id}`
- 配置文件：`config/version.php`
- 数据表：`light_system_version`、`light_upgrade_task`
- 初始化脚本：`sql/install.sql`

推荐在 `.env` 中配置：

```ini
[UPGRADE]
SOURCE = github
OWNER = your-name
REPO = light-admin
RELEASE_TOKEN =
ASSET_PATTERN = light-admin-{version}.zip
INCLUDE_PRERELEASE = false
TIMEOUT = 60
```

公开仓库可以不配置 `RELEASE_TOKEN`；私有仓库需要配置 token，否则无法读取 Release 和下载资源。

如果使用 GitLab，则配置：

```ini
[UPGRADE]
SOURCE = gitlab
PROJECT = group/light-admin
RELEASE_TOKEN =
RELEASE_TOKEN_HEADER = PRIVATE-TOKEN: {token}
ASSET_PATTERN = light-admin-{version}.zip
TIMEOUT = 60
```

如果使用 Gitee，则配置 `SOURCE = gitee`、`OWNER`、`REPO` 和 `ASSET_PATTERN`。

如果使用腾讯 CNB，则配置：

```ini
[UPGRADE]
SOURCE = cnb
PROJECT = your-org/light-admin
RELEASE_TOKEN = your-cnb-token
ASSET_PATTERN = light-admin-{version}.zip
TIMEOUT = 60
```
