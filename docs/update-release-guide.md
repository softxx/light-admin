# 增量更新发版说明

本文只说明“后台版本管理中心”使用的增量更新包发版流程。全量安装包可以按完整项目另行打包，不放在本文范围内。

## 一、发版约定

每次发版需要准备两个东西：

- 一个发行版 tag，例如 `v1.0.1` 或 `1.0.1`。
- 一个 zip 更新包，文件名需要和后台 `ASSET_PATTERN` 一致，默认是 `light-admin-{version}.zip`，例如 `light-admin-1.0.1.zip`。

后台检查更新时会读取发行版平台的 Release，按 tag 判断版本，并在 Release assets 中查找匹配的 zip 包。

## 二、更新包目录结构

更新包根目录支持这些目录：

```text
light-admin-1.0.1.zip
├── backend/
│   ├── app/
│   ├── config/
│   ├── core/
│   ├── public/
│   ├── route/
│   ├── vendor/
│   ├── composer.json
│   ├── composer.lock
│   ├── think
│   └── VERSION
├── frontend/
│   └── dist/
├── migrations/
│   ├── 20260424_001.sql
│   └── rollback_20260424_001.sql
└── scripts/
    ├── precheck.php
    └── post_upgrade.php
```

增量包只放本次需要更新的内容，没有变化的目录不要放。

需要特别注意：当前升级器会把 `backend` 下出现的顶层目录当作整体替换单元。也就是说，如果包里放了 `backend/app`，升级时会先备份并替换整个项目 `app` 目录。因此 `backend/app`、`backend/config`、`backend/core`、`backend/public`、`backend/route`、`backend/vendor` 这些目录不要只放单个改动文件，应该放完整目录。

每次发版建议至少包含完整 `backend/config`，确保 `config/version.php` 中的版本号和构建号被更新。

## 三、哪些内容应该放进更新包

后端代码变更：

- 改了 `app`，放完整 `backend/app`。
- 改了 `route`，放完整 `backend/route`。
- 改了 `core`，放完整 `backend/core`。
- 改了配置，放完整 `backend/config`。
- 改了 `composer.json` 或依赖版本，先执行生产依赖安装，再放 `backend/vendor`、`backend/composer.json`、`backend/composer.lock`。

前端代码变更：

- 执行 `pnpm build`。
- 把 `web_art/dist` 放到 `frontend/dist`。

数据库变更：

- 新增 SQL 文件放到 `migrations`。
- 正向迁移文件不要以 `rollback_` 开头。
- 回滚 SQL 文件必须以 `rollback_` 开头。
- SQL 文件会按文件名排序执行，建议使用时间戳前缀。

脚本变更：

- 升级前检查放 `scripts/precheck.php`。
- 升级后处理放 `scripts/post_upgrade.php`。
- 脚本中可以读取 `$context`，里面包含 `root_path`、`runtime_path`、`version`、`task_id` 等信息。

## 四、发版前修改版本号

后端版本号在：

```text
config/version.php
```

前端版本号在：

```text
web_art/.env
```

发版前建议同步修改：

```text
config/version.php -> version / build / released_at
web_art/.env -> VITE_VERSION
```

如果这次更新包包含前端构建结果，也要确保前端重新构建后再打包。

## 五、打包示例

下面是一个 PowerShell 示例，只演示目录组织方式，实际可以按本次变更选择要复制的目录：

```powershell
$version = "1.0.1"
$stage = "runtime/release/light-admin-$version"
$zip = "runtime/release/light-admin-$version.zip"

Remove-Item -Recurse -Force $stage -ErrorAction SilentlyContinue
New-Item -ItemType Directory -Force "$stage/backend" | Out-Null
New-Item -ItemType Directory -Force "$stage/frontend" | Out-Null
New-Item -ItemType Directory -Force "$stage/migrations" | Out-Null

Copy-Item -Recurse -Force "app" "$stage/backend/app"
Copy-Item -Recurse -Force "config" "$stage/backend/config"
Copy-Item -Recurse -Force "route" "$stage/backend/route"
Copy-Item -Recurse -Force "web_art/dist" "$stage/frontend/dist"
Copy-Item -Force "sql/upgrade/20260424_001.sql" "$stage/migrations/20260424_001.sql"

Compress-Archive -Path "$stage/*" -DestinationPath $zip -Force
```

如果本次没有数据库变更，可以不创建 `migrations`。如果本次没有前端变更，可以不放 `frontend/dist`。

## 六、生成 SHA256

建议每个更新包都同时上传一个同名 `.sha256` 文件：

```powershell
$zip = "runtime/release/light-admin-1.0.1.zip"
$hash = (Get-FileHash $zip -Algorithm SHA256).Hash.ToLower()
"$hash  light-admin-1.0.1.zip" | Set-Content "$zip.sha256" -Encoding ascii
```

最终上传到 Release assets：

```text
light-admin-1.0.1.zip
light-admin-1.0.1.zip.sha256
```

后台下载升级包后会读取 `.sha256` 并校验包完整性。

## 七、Release 内容格式

Release tag：

```text
v1.0.1
```

Release assets：

```text
light-admin-1.0.1.zip
light-admin-1.0.1.zip.sha256
```

Release body 可以写正常更新说明，也可以追加隐藏升级元信息：

```markdown
修复内容：
- 修复用户列表筛选问题
- 优化登录日志查询性能

<!-- upgrade
min_upgradable_version=1.0.0
php=>=8.2
database_migration=true
required=false
-->
```

字段说明：

- `min_upgradable_version`：允许升级的最低当前版本。
- `php`：目标版本要求的 PHP 版本。
- `database_migration`：是否包含数据库迁移。
- `required`：是否强制升级。

## 八、不同平台上传位置

GitHub：

- 创建 GitHub Release。
- tag 使用 `v1.0.1` 或 `1.0.1`。
- 上传 zip 和 `.sha256` 到 Release assets。

GitLab：

- 创建 GitLab Release。
- 上传 zip 和 `.sha256` 到 Release assets/links。
- 后台配置 `SOURCE = gitlab`、`PROJECT = group/project`。

Gitee：

- 创建 Gitee Release。
- 上传 zip 和 `.sha256`。
- 后台配置 `SOURCE = gitee`、`OWNER`、`REPO`。

腾讯 CNB：

- 创建 CNB Release。
- 上传 zip 和 `.sha256` 到发行版附件。
- 后台配置 `SOURCE = cnb`、`PROJECT = 组织/仓库`、`RELEASE_TOKEN`。

## 九、发布后验证

发版后进入后台：

1. 系统管理 / 版本管理。
2. 点击“检查更新”。
3. 确认最新版本号、发布时间、升级包名称正确。
4. 点击“下载”，确认 SHA256 校验通过。
5. 点击“预检查”，确认没有失败项。
6. 再执行“一键升级”。

如果检查不到版本，优先确认：

- `.env` 中的 `SOURCE`、`OWNER`、`REPO` 或 `PROJECT` 是否正确。
- Release tag 是否是 `v1.0.1` 或 `1.0.1`。
- zip 文件名是否匹配 `ASSET_PATTERN`。
- 私有仓库是否配置了 `RELEASE_TOKEN`。
- CNB 是否配置了可访问 Open API 的 token。

## 十、常见注意事项

- 不要把 `.env`、`runtime`、上传目录、日志目录打进更新包。
- 改了依赖再放 `vendor`，否则不要放 `backend/vendor`。
- 只要包里有 `migrations/*.sql`，升级前会做数据库备份。
- 复杂 SQL 尽量拆成简单语句并用分号结尾，不要依赖 `DELIMITER`。
- 删除旧文件或清理旧资源，可以在 `scripts/post_upgrade.php` 里处理。
- 完整 `frontend/dist` 会整体替换 `public` 下对应资源；确实要额外清理时也放到 `post_upgrade.php`。
