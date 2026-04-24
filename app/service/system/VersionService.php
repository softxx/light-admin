<?php

namespace app\service\system;

use app\model\system\SystemVersion;
use app\model\system\UpgradeTask;
use core\base\BaseService;
use core\exception\FailedException;
use think\facade\Db;

/**
 * 版本管理中心服务。
 *
 * 负责读取当前版本、从发布源发现新版本、下载并校验升级包，以及创建升级/回滚任务。
 */
class VersionService extends BaseService
{
    // 这些状态代表任务已经结束，不再阻塞新的升级任务。
    private const TERMINAL_STATUSES = ['success', 'failed', 'rolled_back', 'rollback_failed'];

    /**
     * 当前版本、发布配置、环境信息和最近任务概览。
     */
    public function current(): array
    {
        $this->ensureSuperAdmin();

        return [
            'current' => $this->getCurrentVersion(),
            'release' => $this->releaseConfigForClient(),
            'installed' => $this->latestInstalledVersion(),
            'last_task' => $this->latestTask(),
            'environment' => $this->environmentInfo(),
        ];
    }

    /**
     * 从配置的发布源中检查指定版本或最新版本。
     */
    public function check(array $params): array
    {
        $this->ensureSuperAdmin();

        [$sourceUrl, $manifest] = $this->loadManifest($params);
        $latest = $this->resolveVersionEntry($manifest, (string) ($params['version'] ?? ''));
        $current = $this->getCurrentVersion();

        return [
            'current' => $current,
            'source_url' => $sourceUrl,
            'manifest' => [
                'app' => $manifest['app'] ?? '',
                'channel' => $manifest['channel'] ?? config('version.release.channel', 'stable'),
                'latest' => $manifest['latest'] ?? ($latest['version'] ?? ''),
                'source' => $manifest['source'] ?? $this->releaseSource($params),
                'platform' => $manifest['platform'] ?? [],
            ],
            'latest' => $latest,
            'upgrade_available' => $latest
                ? version_compare($this->normalizeVersion($latest['version']), $this->normalizeVersion($current['version']), '>')
                : false,
        ];
    }

    /**
     * 下载升级包，并在下载完成后立即做 sha256 校验。
     */
    public function download(array $params): array
    {
        $this->ensureSuperAdmin();

        [$sourceUrl, $manifest] = $this->loadManifest($params);
        $version = $this->resolveVersionEntry($manifest, (string) ($params['version'] ?? ''));
        if (!$version) {
            throw new FailedException('未找到可下载的版本');
        }

        $result = $this->downloadPackage($version);
        return array_merge($result, [
            'source_url' => $sourceUrl,
            'version' => $version,
        ]);
    }

    /**
     * 升级前检查环境、权限、磁盘空间、发布源配置和任务锁。
     */
    public function precheck(array $params): array
    {
        $this->ensureSuperAdmin();

        [$sourceUrl, $manifest] = $this->loadManifest($params);
        $version = $this->resolveVersionEntry($manifest, (string) ($params['version'] ?? ''));
        if (!$version) {
            throw new FailedException('未找到可预检的版本');
        }

        return array_merge($this->runPrecheck($version, (string) ($params['package_path'] ?? '')), [
            'source_url' => $sourceUrl,
            'version' => $version,
        ]);
    }

    /**
     * 创建升级任务并交给 UpgradeService 拉起后台 CLI。
     */
    public function startUpgrade(array $params): array
    {
        $this->ensureSuperAdmin();

        [$sourceUrl, $manifest] = $this->loadManifest($params);
        $version = $this->resolveVersionEntry($manifest, (string) ($params['version'] ?? ''));
        if (!$version) {
            throw new FailedException('未找到可升级的版本');
        }

        $activeTask = UpgradeTask::whereNotIn('status', self::TERMINAL_STATUSES)->order('id', 'desc')->find();
        if ($activeTask) {
            throw new FailedException('已有升级任务正在执行，请等待完成后再操作');
        }

        $task = UpgradeTask::create([
            'target_version' => $version['version'],
            'package_url' => (string) ($version['package_url'] ?? ''),
            'package_path' => (string) ($params['package_path'] ?? ''),
            'manifest_url' => $sourceUrl,
            'manifest' => json_encode($version, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'precheck' => json_encode([], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'status' => 'pending',
            'progress' => 0,
            'message' => '升级任务已创建',
            'logs' => json_encode([[
                'time' => date('Y-m-d H:i:s'),
                'level' => 'info',
                'message' => '升级任务已创建',
            ]], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'operator_id' => $this->currentUserId(),
        ]);

        /** @var UpgradeService $upgradeService */
        $upgradeService = app()->make(UpgradeService::class);
        $upgradeService->launchTask((int) $task->id);

        return $this->task((int) $task->id);
    }

    /**
     * 创建回滚任务，默认回滚最近一次成功升级的备份。
     */
    public function startRollback(array $params): array
    {
        $this->ensureSuperAdmin();

        $taskId = (int) ($params['task_id'] ?? 0);
        if ($taskId <= 0) {
            $task = UpgradeTask::where('status', 'success')->where('backup_path', '<>', '')->order('id', 'desc')->find();
        } else {
            $task = UpgradeTask::find($taskId);
        }

        if (!$task) {
            throw new FailedException('未找到可回滚的升级任务');
        }

        if ((string) $task->backup_path === '') {
            throw new FailedException('该任务没有可用备份，无法回滚');
        }

        /** @var UpgradeService $upgradeService */
        $upgradeService = app()->make(UpgradeService::class);
        $upgradeService->launchRollback((int) $task->id);

        return $this->task((int) $task->id);
    }

    public function task(int $id): array
    {
        $task = UpgradeTask::find($id);
        if (!$task) {
            throw new FailedException('升级任务不存在');
        }

        return $this->normalizeTask($task->toArray());
    }

    public function tasks(int $limit = 20): array
    {
        $this->ensureSuperAdmin();

        $limit = max(1, min($limit, 50));
        return array_map(
            fn(array $task) => $this->normalizeTask($task),
            UpgradeTask::order('id', 'desc')->limit($limit)->select()->toArray()
        );
    }

    public function getCurrentVersion(): array
    {
        return [
            'version' => (string) config('version.version', '1.0.0'),
            'build' => (string) config('version.build', ''),
            'commit' => (string) config('version.commit', ''),
            'released_at' => (string) config('version.released_at', ''),
            'channel' => (string) config('version.release.channel', 'stable'),
        ];
    }

    /**
     * 读取并转换发布源数据为内部统一的版本元数据。
     *
     * 这里保持 loadManifest 命名，是为了复用原升级任务字段；实际来源由 release.source 决定。
     */
    public function loadManifest(array|string $params = []): array
    {
        $params = is_array($params) ? $params : [];
        $source = $this->releaseSource($params);

        if (!in_array($source, ['github', 'gitlab', 'gitee', 'cnb'], true)) {
            throw new FailedException('不支持的发布源类型：' . $source);
        }

        $platform = $this->releasePlatformConfig($params, $source);
        $release = $this->loadPlatformRelease($platform, (string) ($params['version'] ?? ''));
        $version = $this->normalizePlatformRelease($release, $platform);
        $sourceUrl = (string) ($version['release_url'] ?? $this->releasePageUrl($platform, (string) ($version['tag_name'] ?? '')));

        return [$sourceUrl, [
            'app' => (string) config('version.release.app', 'light-admin'),
            'channel' => $version['channel'] ?? config('version.release.channel', 'stable'),
            'source' => $source,
            'latest' => $version['version'],
            'platform' => [
                'source' => $source,
                'owner' => $platform['owner'],
                'repo' => $platform['repo'],
                'project' => $platform['project'],
                'asset_pattern' => $platform['asset_pattern'],
                'include_prerelease' => $platform['include_prerelease'],
            ],
            'versions' => [$version],
        ]];
    }

    /**
     * 从统一版本元数据中解析目标版本；未指定时取 latest 或版本号最大的记录。
     */
    public function resolveVersionEntry(array $manifest, string $targetVersion = ''): ?array
    {
        $versions = array_values(array_filter($manifest['versions'] ?? [], 'is_array'));
        if (!$versions) {
            return null;
        }

        if ($targetVersion !== '') {
            foreach ($versions as $version) {
                if ($this->normalizeVersion((string) ($version['version'] ?? '')) === $this->normalizeVersion($targetVersion)) {
                    return $this->normalizeVersionEntry($version);
                }
            }
            return null;
        }

        $latest = (string) ($manifest['latest'] ?? '');
        if ($latest !== '') {
            foreach ($versions as $version) {
                if ($this->normalizeVersion((string) ($version['version'] ?? '')) === $this->normalizeVersion($latest)) {
                    return $this->normalizeVersionEntry($version);
                }
            }
        }

        usort($versions, function (array $left, array $right) {
            return version_compare(
                $this->normalizeVersion((string) ($right['version'] ?? '')),
                $this->normalizeVersion((string) ($left['version'] ?? ''))
            );
        });

        return $this->normalizeVersionEntry($versions[0]);
    }

    /**
     * 只做检查不改变系统，用于前端提前暴露升级风险。
     */
    public function runPrecheck(array $version, string $packagePath = ''): array
    {
        $checks = [];
        $current = $this->getCurrentVersion();
        $workDir = $this->workDir();

        $this->appendCheck($checks, 'version', '当前版本', true, '当前版本 ' . $current['version']);

        $minVersion = (string) ($version['min_upgradable_version'] ?? '');
        $minOk = $minVersion === '' || version_compare($this->normalizeVersion($current['version']), $this->normalizeVersion($minVersion), '>=');
        $this->appendCheck(
            $checks,
            'min_version',
            '最小可升级版本',
            $minOk,
            $minOk ? '满足升级要求' : '当前版本低于要求的 ' . $minVersion
        );

        $phpConstraint = (string) ($version['php'] ?? '');
        $phpOk = $phpConstraint === '' || $this->matchPhpConstraint($phpConstraint);
        $this->appendCheck(
            $checks,
            'php',
            'PHP 版本',
            $phpOk,
            $phpConstraint === '' ? PHP_VERSION : PHP_VERSION . ' / ' . $phpConstraint
        );

        $this->appendCheck($checks, 'zip', 'Zip 扩展', class_exists(\ZipArchive::class), '用于解压升级包');

        $workDirReady = $this->ensureDirectory($workDir) && is_writable($workDir);
        $this->appendCheck($checks, 'work_dir', '升级工作目录', $workDirReady, $workDir);

        $rootPath = app()->getRootPath();
        $rootWritable = is_writable($rootPath);
        $this->appendCheck($checks, 'root_writable', '项目目录写权限', $rootWritable, $rootPath);

        $packageUrl = (string) ($version['package_url'] ?? '');
        $this->appendCheck($checks, 'package_url', '升级包地址', $packageUrl !== '', $packageUrl !== '' ? $packageUrl : '发布源缺少 zip 升级包');

        $freeSpace = @disk_free_space($rootPath);
        $packageSize = (int) ($version['size_bytes'] ?? 0);
        $needSpace = max($packageSize * 3, 100 * 1024 * 1024);
        $diskOk = $freeSpace === false || $freeSpace >= $needSpace;
        $this->appendCheck($checks, 'disk', '磁盘空间', $diskOk, $freeSpace === false ? '无法读取磁盘空间，升级时会继续检查' : $this->formatBytes((int) $freeSpace));

        $sourceName = $this->releaseSourceLabel((string) ($version['source'] ?? config('version.release.source', 'github')));
        $sourceOk = (string) ($version['package_url'] ?? '') !== '';
        $this->appendCheck(
            $checks,
            'release_source',
            '发布源',
            $sourceOk,
            $sourceOk ? $sourceName . ' / ' . (string) ($version['release_url'] ?? $packageUrl) : '未解析到升级包地址'
        );

        $hasRemoteSha256 = (string) ($version['sha256'] ?? '') !== '';
        $this->appendCheck(
            $checks,
            'package_sha256',
            '升级包摘要',
            $hasRemoteSha256,
            $hasRemoteSha256 ? '已读取发布源 sha256' : '发布源未提供 sha256，建议额外提供 .sha256 或在 Release 元信息中填写 sha256',
            $hasRemoteSha256 ? 'pass' : 'warn'
        );

        $processOk = self::canLaunchBackgroundProcess();
        $this->appendCheck($checks, 'process', '后台任务能力', $processOk, $processOk ? '可以拉起 CLI 升级任务' : 'popen/proc_open 不可用');

        try {
            Db::query('SELECT 1');
            $dbOk = true;
            $dbMessage = '数据库连接正常';
        } catch (\Throwable $e) {
            $dbOk = false;
            $dbMessage = $e->getMessage();
        }
        $this->appendCheck($checks, 'database', '数据库连接', $dbOk, $dbMessage);

        $lockPath = $this->upgradeLockPath();
        $lockOk = !is_file($lockPath);
        $this->appendCheck($checks, 'upgrade_lock', '升级锁', $lockOk, $lockOk ? '没有正在执行的升级任务' : $lockPath);

        if ($packagePath !== '') {
            $packageExists = is_file($packagePath);
            $this->appendCheck($checks, 'package_file', '本地升级包', $packageExists, $packageExists ? $packagePath : '文件不存在');
        }

        $failedCount = count(array_filter($checks, fn(array $item) => $item['status'] === 'fail'));
        $warningCount = count(array_filter($checks, fn(array $item) => $item['status'] === 'warn'));

        return [
            'can_upgrade' => $failedCount === 0,
            'failed_count' => $failedCount,
            'warning_count' => $warningCount,
            'checks' => $checks,
        ];
    }

    /**
     * 下载升级包到 runtime/upgrade/packages，并统一走校验流程。
     */
    public function downloadPackage(array $version): array
    {
        $packageUrl = (string) ($version['package_url'] ?? '');
        if ($packageUrl === '') {
            throw new FailedException('发布源缺少升级包地址');
        }

        $packageDir = $this->workDir('packages');
        $this->ensureDirectory($packageDir);
        $fileName = $this->safePackageFileName((string) ($version['version'] ?? 'latest'), (string) ($version['asset_name'] ?? ''));
        $targetPath = $packageDir . DIRECTORY_SEPARATOR . $fileName;
        $tempPath = $targetPath . '.downloading';

        if (is_file($tempPath)) {
            @unlink($tempPath);
        }

        if ($this->isLocalFileUrl($packageUrl)) {
            $sourcePath = $this->localPathFromUrl($packageUrl);
            if (!@copy($sourcePath, $tempPath)) {
                throw new FailedException('复制本地升级包失败');
            }
        } else {
            $downloadUrl = $packageUrl;
            $headers = $this->downloadHeadersForVersion($version);
            if ((string) ($version['source'] ?? '') === 'github' && (string) ($version['asset_api_url'] ?? '') !== '' && $this->githubToken() !== '') {
                $downloadUrl = (string) $version['asset_api_url'];
                $headers = $this->githubHeaders(true);
            }

            $this->assertAllowedUrl($downloadUrl);
            $this->downloadRemoteFile($downloadUrl, $tempPath, $headers);
        }

        if (is_file($targetPath)) {
            @unlink($targetPath);
        }

        if (!@rename($tempPath, $targetPath)) {
            @unlink($tempPath);
            throw new FailedException('保存升级包失败');
        }

        $verification = $this->verifyPackage($targetPath, $version);

        return [
            'package_path' => $targetPath,
            'size_bytes' => (int) filesize($targetPath),
            'sha256' => hash_file('sha256', $targetPath),
            'verification' => $verification,
        ];
    }

    /**
     * 校验升级包 sha256，优先使用发布源提供的摘要。
     */
    public function verifyPackage(string $packagePath, array $version): array
    {
        if (!is_file($packagePath)) {
            throw new FailedException('升级包文件不存在');
        }

        $sha256 = hash_file('sha256', $packagePath);
        $expectedSha256 = strtolower((string) ($version['sha256'] ?? ''));
        if ($expectedSha256 !== '' && strtolower($sha256) !== $expectedSha256) {
            throw new FailedException('升级包 sha256 校验失败');
        }

        return [
            'sha256' => $sha256,
            'remote_sha256' => $expectedSha256,
            'digest_checked' => $expectedSha256 !== '',
            'sha256_matched' => $expectedSha256 === '' || strtolower($sha256) === $expectedSha256,
        ];
    }

    /**
     * 升级工作目录。
     */
    public function workDir(string $child = ''): string
    {
        $base = (string) config('version.paths.work_dir', 'runtime/upgrade');
        $path = $this->absolutePath($base);
        return $child === '' ? $path : $path . DIRECTORY_SEPARATOR . trim($child, '/\\');
    }

    public function maintenanceLockPath(): string
    {
        return $this->absolutePath((string) config('version.paths.maintenance_lock', 'runtime/maintenance.lock'));
    }

    public function upgradeLockPath(): string
    {
        return $this->workDir('upgrade.lock');
    }

    /**
     * 判断当前环境能否从 Web 进程拉起后台 CLI。
     */
    public static function canLaunchBackgroundProcess(): bool
    {
        return self::functionAvailable('popen') || self::functionAvailable('proc_open');
    }

    public static function functionAvailable(string $function): bool
    {
        if (!function_exists($function)) {
            return false;
        }

        $disabled = array_map('trim', explode(',', (string) ini_get('disable_functions')));
        return !in_array($function, $disabled, true);
    }

    /**
     * 将数据库里的 JSON 字段转成前端可直接消费的数组结构。
     */
    public function normalizeTask(array $task): array
    {
        foreach (['manifest', 'logs', 'precheck'] as $jsonField) {
            $value = $task[$jsonField] ?? null;
            if (is_string($value) && $value !== '') {
                $decoded = json_decode($value, true);
                $task[$jsonField] = is_array($decoded) ? $decoded : [];
            } elseif (!is_array($value)) {
                $task[$jsonField] = [];
            }
        }

        $task['id'] = (int) ($task['id'] ?? 0);
        $task['progress'] = (int) ($task['progress'] ?? 0);
        $task['operator_id'] = (int) ($task['operator_id'] ?? 0);
        $task['is_running'] = !in_array((string) ($task['status'] ?? ''), self::TERMINAL_STATUSES, true);

        return $task;
    }

    public function formatBytes(int $bytes): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $index = 0;
        $value = $bytes;
        while ($value >= 1024 && $index < count($units) - 1) {
            $value /= 1024;
            $index++;
        }

        return ($value >= 10 || $index === 0 ? number_format($value, 0) : number_format($value, 1)) . ' ' . $units[$index];
    }

    private function ensureSuperAdmin(): void
    {
        if (!is_super_admin()) {
            throw new FailedException('仅超级管理员可操作', 403, [], 403);
        }
    }

    private function releaseConfigForClient(): array
    {
        $source = $this->releaseSource();
        $platform = $this->releasePlatformConfig(['source' => $source], $source, false);

        return [
            'app' => (string) config('version.release.app', 'light-admin'),
            'channel' => (string) config('version.release.channel', 'stable'),
            'source' => $source,
            'has_release_token' => $this->releaseToken() !== '',
            'owner' => $platform['owner'],
            'repo' => $platform['repo'],
            'project' => $platform['project'],
            'api_base' => $platform['api_base'],
            'asset_pattern' => $platform['asset_pattern'],
            'include_prerelease' => $platform['include_prerelease'],
        ];
    }

    private function latestInstalledVersion(): array
    {
        $record = SystemVersion::order('id', 'desc')->find();
        return $record ? $record->toArray() : [];
    }

    private function latestTask(): array
    {
        $task = UpgradeTask::order('id', 'desc')->find();
        return $task ? $this->normalizeTask($task->toArray()) : [];
    }

    private function environmentInfo(): array
    {
        return [
            'php_version' => PHP_VERSION,
            'thinkphp_version' => app()->version(),
            'root_path' => app()->getRootPath(),
            'runtime_path' => app()->getRuntimePath(),
            'os' => PHP_OS_FAMILY,
        ];
    }

    private function releaseSource(array $params = []): string
    {
        $source = strtolower(trim((string) ($params['source'] ?? config('version.release.source', 'github'))));
        return in_array($source, ['github', 'gitlab', 'gitee', 'cnb'], true) ? $source : $source;
    }

    private function releaseSourceLabel(string $source): string
    {
        return match (strtolower($source)) {
            'github' => 'GitHub Releases',
            'gitlab' => 'GitLab Releases',
            'gitee' => 'Gitee Releases',
            'cnb' => '腾讯 CNB Releases',
            default => $source,
        };
    }

    private function releaseToken(): string
    {
        return trim((string) config('version.release.release_token', ''));
    }

    private function releaseHeaders(): array
    {
        $token = $this->releaseToken();
        if ($token === '') {
            return [];
        }

        $header = trim((string) config('version.release.release_token_header', 'Authorization: Bearer {token}'));
        if ($header === '') {
            return [];
        }

        return [str_replace('{token}', $token, $header)];
    }

    private function downloadHeadersForVersion(array $version): array
    {
        $source = strtolower((string) ($version['source'] ?? config('version.release.source', 'github')));
        if (in_array($source, ['gitlab', 'gitee', 'cnb'], true)) {
            return $this->platformHeaders(['source' => $source]);
        }

        return $this->releaseHeaders();
    }

    private function releasePlatformConfig(array $params = [], string $source = '', bool $require = true): array
    {
        $source = $source !== '' ? $source : $this->releaseSource($params);
        $owner = trim((string) ($params['owner'] ?? config('version.release.owner', '')));
        $repo = trim((string) ($params['repo'] ?? config('version.release.repo', '')));
        if ($owner === '') {
            $owner = trim((string) ($params['github_owner'] ?? config('version.release.github_owner', '')));
        }
        if ($repo === '') {
            $repo = trim((string) ($params['github_repo'] ?? config('version.release.github_repo', '')));
        }
        $project = trim((string) ($params['project'] ?? config('version.release.project', '')));

        if ($owner === '' && str_contains($repo, '/')) {
            [$owner, $repo] = array_pad(explode('/', $repo, 2), 2, '');
            $owner = trim($owner);
            $repo = trim($repo);
        }

        if ($repo === '' && str_contains($owner, '/')) {
            [$owner, $repo] = array_pad(explode('/', $owner, 2), 2, '');
            $owner = trim($owner);
            $repo = trim($repo);
        }

        if ($project === '' && $owner !== '' && $repo !== '') {
            $project = $owner . '/' . $repo;
        }

        if ($require) {
            if (in_array($source, ['gitlab', 'cnb'], true) && $project === '') {
                throw new FailedException('请先配置 ' . $this->releaseSourceLabel($source) . ' 项目标识');
            }
            if (!in_array($source, ['gitlab', 'cnb'], true) && ($owner === '' || $repo === '')) {
                throw new FailedException('请先配置发行版平台 owner 和 repo');
            }
        }

        if (($owner !== '' && !preg_match('/^[A-Za-z0-9_.-]+$/', $owner))
            || ($repo !== '' && !preg_match('/^[A-Za-z0-9_.-]+$/', $repo))) {
            throw new FailedException('发行版平台 owner 或 repo 格式不正确');
        }

        return [
            'source' => $source,
            'owner' => $owner,
            'repo' => $repo,
            'project' => $project,
            'api_base' => rtrim($this->platformApiBase($source), '/'),
            'asset_pattern' => trim((string) ($params['asset_pattern'] ?? $params['github_asset_pattern'] ?? config('version.release.asset_pattern', config('version.release.github_asset_pattern', 'light-admin-{version}.zip')))),
            'include_prerelease' => $this->booleanValue($params['include_prerelease'] ?? config('version.release.include_prerelease', false)),
        ];
    }

    private function platformApiBase(string $source): string
    {
        $configured = trim((string) config('version.release.api_base', ''));
        if ($configured !== '') {
            return $configured;
        }

        return match ($source) {
            'gitlab' => 'https://gitlab.com/api/v4',
            'gitee' => 'https://gitee.com/api/v5',
            'cnb' => 'https://api.cnb.cool',
            default => (string) config('version.release.github_api_base', 'https://api.github.com'),
        };
    }

    /**
     * 按发行版平台读取 Release，后续增加平台时只需要补这里的 API 适配。
     */
    private function loadPlatformRelease(array $platform, string $targetVersion = ''): array
    {
        return match ($platform['source']) {
            'gitlab' => $this->loadGitLabRelease($platform, $targetVersion),
            'gitee' => $this->loadGiteeRelease($platform, $targetVersion),
            'cnb' => $this->loadCnbRelease($platform, $targetVersion),
            default => $this->loadGitHubRelease($platform, $targetVersion),
        };
    }

    private function normalizePlatformRelease(array $release, array $platform): array
    {
        if ($platform['source'] === 'github') {
            return $this->normalizeGitHubRelease($release, $platform);
        }

        return $this->normalizeGenericRelease($release, $platform);
    }

    /**
     * 获取 GitHub Release。默认走 latest，显式版本会兼容 1.0.1 和 v1.0.1 两种 tag。
     */
    private function loadGitHubRelease(array $github, string $targetVersion = ''): array
    {
        if ($targetVersion !== '') {
            foreach ($this->candidateTags($targetVersion) as $tag) {
                $release = $this->readGitHubJson($github, 'repos/' . $github['owner'] . '/' . $github['repo'] . '/releases/tags/' . rawurlencode($tag), true);
                if ($release) {
                    return $release;
                }
            }

            throw new FailedException('GitHub Release 中未找到目标版本：' . $targetVersion);
        }

        if ($github['include_prerelease']) {
            $releases = $this->readGitHubJson($github, 'repos/' . $github['owner'] . '/' . $github['repo'] . '/releases?per_page=20');
            foreach ($releases as $release) {
                if (is_array($release) && empty($release['draft'])) {
                    return $release;
                }
            }

            throw new FailedException('GitHub Release 列表为空');
        }

        return $this->readGitHubJson($github, 'repos/' . $github['owner'] . '/' . $github['repo'] . '/releases/latest');
    }

    private function loadGitLabRelease(array $platform, string $targetVersion = ''): array
    {
        $project = rawurlencode($platform['project']);
        if ($targetVersion !== '') {
            foreach ($this->candidateTags($targetVersion) as $tag) {
                $release = $this->readPlatformJson($platform, 'projects/' . $project . '/releases/' . rawurlencode($tag), true);
                if ($release) {
                    return $release;
                }
            }

            throw new FailedException('GitLab Release 中未找到目标版本：' . $targetVersion);
        }

        return $this->readPlatformJson($platform, 'projects/' . $project . '/releases/permalink/latest');
    }

    private function loadGiteeRelease(array $platform, string $targetVersion = ''): array
    {
        if ($targetVersion !== '') {
            foreach ($this->candidateTags($targetVersion) as $tag) {
                $release = $this->readPlatformJson($platform, 'repos/' . $platform['owner'] . '/' . $platform['repo'] . '/releases/tags/' . rawurlencode($tag), true);
                if ($release) {
                    return $release;
                }
            }

            throw new FailedException('Gitee Release 中未找到目标版本：' . $targetVersion);
        }

        $release = $this->readPlatformJson($platform, 'repos/' . $platform['owner'] . '/' . $platform['repo'] . '/releases/latest', true);
        if ($release) {
            return $release;
        }

        $releases = $this->readPlatformJson($platform, 'repos/' . $platform['owner'] . '/' . $platform['repo'] . '/releases?per_page=20');
        foreach ($releases as $item) {
            if (is_array($item)) {
                return $item;
            }
        }

        throw new FailedException('Gitee Release 列表为空');
    }

    private function loadCnbRelease(array $platform, string $targetVersion = ''): array
    {
        if ($this->releaseToken() === '') {
            throw new FailedException('腾讯 CNB Open API 需要配置 RELEASE_TOKEN');
        }

        $repoPath = $this->cnbRepoPath($platform);
        if ($targetVersion !== '') {
            foreach ($this->candidateTags($targetVersion) as $tag) {
                $release = $this->readPlatformJson($platform, $repoPath . '/-/releases/tags/' . rawurlencode($tag), true);
                if ($release) {
                    return $release;
                }
            }

            throw new FailedException('腾讯 CNB Release 中未找到目标版本：' . $targetVersion);
        }

        if (!$platform['include_prerelease']) {
            $release = $this->readPlatformJson($platform, $repoPath . '/-/releases/latest', true);
            if ($release && empty($release['draft']) && empty($release['prerelease'])) {
                return $release;
            }
        }

        $releases = $this->readPlatformJson($platform, $repoPath . '/-/releases?page=1&page_size=20');
        foreach ($releases as $release) {
            if (!is_array($release) || !empty($release['draft'])) {
                continue;
            }

            if ($platform['include_prerelease'] || empty($release['prerelease'])) {
                return $release;
            }
        }

        throw new FailedException('腾讯 CNB Release 列表为空');
    }

    private function readPlatformJson(array $platform, string $path, bool $allowNotFound = false): ?array
    {
        $response = $this->readRemoteContentWithStatus($this->platformApiUrl($platform, $path), $this->platformHeaders($platform));
        if ($response['status'] === 404 && $allowNotFound) {
            return null;
        }

        if ($response['status'] < 200 || $response['status'] >= 300) {
            $message = $this->githubErrorMessage($response['content']);
            throw new FailedException('读取 ' . $this->releaseSourceLabel($platform['source']) . ' 失败：' . ($message ?: 'HTTP ' . $response['status']));
        }

        $decoded = json_decode($response['content'], true);
        if (!is_array($decoded)) {
            throw new FailedException($this->releaseSourceLabel($platform['source']) . ' 响应不是有效 JSON');
        }

        return $decoded;
    }

    private function readGitHubJson(array $github, string $path, bool $allowNotFound = false): ?array
    {
        $response = $this->readRemoteContentWithStatus($this->githubApiUrl($github, $path), $this->githubHeaders());
        if ($response['status'] === 404 && $allowNotFound) {
            return null;
        }

        if ($response['status'] < 200 || $response['status'] >= 300) {
            $message = $this->githubErrorMessage($response['content']);
            throw new FailedException('读取 GitHub Release 失败：' . ($message ?: 'HTTP ' . $response['status']));
        }

        $decoded = json_decode($response['content'], true);
        if (!is_array($decoded)) {
            throw new FailedException('GitHub Release 响应不是有效 JSON');
        }

        return $decoded;
    }

    private function normalizeGitHubRelease(array $release, array $github): array
    {
        if (!empty($release['draft'])) {
            throw new FailedException('草稿 Release 不能用于升级');
        }

        $tag = (string) ($release['tag_name'] ?? '');
        $version = $this->normalizeVersion($tag);
        if ($version === '') {
            throw new FailedException('GitHub Release 缺少 tag_name');
        }

        $asset = $this->findGitHubAsset($release, $github['asset_pattern'], $version, $tag);
        $body = (string) ($release['body'] ?? '');
        $metadata = $this->releaseMetadataFromBody($body);
        $sha256 = $this->sha256FromDigest((string) ($asset['digest'] ?? ''));
        if ($sha256 === '') {
            $sha256 = $this->sha256FromChecksumAsset($release, $asset);
        }

        return [
            'version' => $version,
            'tag_name' => $tag,
            'build' => (string) ($release['name'] ?? $tag),
            'commit' => (string) ($release['target_commitish'] ?? ''),
            'released_at' => (string) ($release['published_at'] ?? $release['created_at'] ?? ''),
            'channel' => !empty($release['prerelease']) ? 'prerelease' : (string) config('version.release.channel', 'stable'),
            'required' => $this->booleanValue($metadata['required'] ?? false),
            'min_upgradable_version' => (string) ($metadata['min_upgradable_version'] ?? ''),
            'php' => (string) ($metadata['php'] ?? ''),
            'database_migration' => $this->booleanValue($metadata['database_migration'] ?? false),
            'package_url' => $this->assetDownloadUrl($asset),
            'asset_api_url' => (string) ($asset['url'] ?? ''),
            'asset_name' => (string) ($asset['name'] ?? ''),
            'size_bytes' => (int) ($asset['size'] ?? 0),
            'sha256' => $sha256,
            'digest' => (string) ($asset['digest'] ?? ''),
            'source' => 'github',
            'release_url' => (string) ($release['html_url'] ?? $this->githubReleasePageUrl($github, $tag)),
            'release_notes' => $this->releaseNotesFromBody($body),
        ];
    }

    private function normalizeGenericRelease(array $release, array $platform): array
    {
        $tag = (string) ($release['tag_name'] ?? $release['tag'] ?? $release['name'] ?? '');
        $version = $this->normalizeVersion($tag);
        if ($version === '') {
            throw new FailedException($this->releaseSourceLabel($platform['source']) . ' Release 缺少 tag_name');
        }

        $asset = $this->findReleaseAsset($release, $platform['asset_pattern'], $version, $tag);
        $body = (string) ($release['body'] ?? $release['description'] ?? '');
        $metadata = $this->releaseMetadataFromBody($body);
        $commit = is_array($release['commit'] ?? null) ? ($release['commit'] ?? []) : [];
        $assetDigest = (string) ($asset['digest'] ?? $asset['checksum'] ?? '');
        if ($assetDigest === '' && strtolower((string) ($asset['hash_algo'] ?? '')) === 'sha256') {
            $assetDigest = (string) ($asset['hash_value'] ?? '');
        }

        $sha256 = $this->sha256FromDigest($assetDigest);
        if ($sha256 === '') {
            $sha256 = (string) ($metadata['sha256'] ?? '');
        }
        if ($sha256 === '') {
            $sha256 = $this->sha256FromChecksumAsset($release, $asset);
        }

        $packageUrl = $this->assetDownloadUrl($asset);
        if ($platform['source'] === 'cnb' && (string) ($asset['name'] ?? '') !== '') {
            // CNB 资源里的 url 是附件信息接口，真正下载 zip 要走 releases/download 接口并携带 token。
            $packageUrl = $this->cnbAssetDownloadUrl($platform, $tag, (string) $asset['name']);
        }
        $releaseUrl = $platform['source'] === 'cnb'
            ? (string) ($release['html_url'] ?? $this->releasePageUrl($platform, $tag))
            : (string) ($release['html_url'] ?? $release['_links']['self'] ?? $release['url'] ?? $this->releasePageUrl($platform, $tag));

        return [
            'version' => $version,
            'tag_name' => $tag,
            'build' => (string) ($release['name'] ?? $tag),
            'commit' => (string) ($release['target_commitish'] ?? $commit['id'] ?? $commit['short_id'] ?? ''),
            'released_at' => (string) ($release['released_at'] ?? $release['published_at'] ?? $release['created_at'] ?? ''),
            'channel' => !empty($release['prerelease']) ? 'prerelease' : (string) config('version.release.channel', 'stable'),
            'required' => $this->booleanValue($metadata['required'] ?? false),
            'min_upgradable_version' => (string) ($metadata['min_upgradable_version'] ?? ''),
            'php' => (string) ($metadata['php'] ?? ''),
            'database_migration' => $this->booleanValue($metadata['database_migration'] ?? false),
            'package_url' => $packageUrl,
            'asset_api_url' => '',
            'asset_name' => (string) ($asset['name'] ?? ''),
            'size_bytes' => (int) ($asset['size'] ?? 0),
            'sha256' => strtolower($sha256),
            'digest' => (string) ($asset['digest'] ?? $asset['checksum'] ?? $asset['hash_value'] ?? ''),
            'source' => $platform['source'],
            'release_url' => $releaseUrl,
            'release_notes' => $this->releaseNotesFromBody($body),
        ];
    }

    private function readRemoteContent(string $url, array $headers = []): string
    {
        if ($this->isLocalFileUrl($url)) {
            $path = $this->localPathFromUrl($url);
            $content = @file_get_contents($path);
            if ($content === false) {
                throw new FailedException('读取本地文件失败');
            }
            return $content;
        }

        $response = $this->readRemoteContentWithStatus($url, $headers);
        if ($response['status'] < 200 || $response['status'] >= 300) {
            throw new FailedException('读取远程文件失败：' . ($response['error'] ?: 'HTTP ' . $response['status']));
        }

        return $response['content'];
    }

    private function readRemoteContentWithStatus(string $url, array $headers = []): array
    {
        $this->assertAllowedUrl($url);
        if (!function_exists('curl_init')) {
            throw new FailedException('当前 PHP 缺少 curl 扩展');
        }

        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => max(10, (int) config('version.release.timeout', 60)),
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);
        if ($headers) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        }

        $content = curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        curl_close($curl);

        return [
            'content' => $content === false ? '' : (string) $content,
            'status' => $status,
            'error' => $error,
        ];
    }

    private function downloadRemoteFile(string $url, string $targetPath, array $headers = []): void
    {
        if (!function_exists('curl_init')) {
            throw new FailedException('当前 PHP 缺少 curl 扩展');
        }

        $handle = @fopen($targetPath, 'wb');
        if (!$handle) {
            throw new FailedException('无法写入升级包临时文件');
        }

        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_FILE => $handle,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => max(30, (int) config('version.release.timeout', 60)),
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);
        if ($headers) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        }

        $ok = curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        curl_close($curl);
        fclose($handle);

        if ($ok === false || $status < 200 || $status >= 300) {
            @unlink($targetPath);
            throw new FailedException('下载升级包失败：' . ($error ?: 'HTTP ' . $status));
        }
    }

    private function assertAllowedUrl(string $url): void
    {
        $parts = parse_url($url);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        if (!in_array($scheme, ['http', 'https'], true)) {
            throw new FailedException('仅支持 http/https 发布地址');
        }

        $host = strtolower((string) ($parts['host'] ?? ''));
        $githubHosts = [
            'api.github.com',
            'github.com',
            'objects.githubusercontent.com',
            'github-releases.githubusercontent.com',
            'raw.githubusercontent.com',
            'gitlab.com',
            'gitee.com',
            'api.cnb.cool',
            'cnb.cool',
        ];
        $apiHost = strtolower((string) (parse_url((string) config('version.release.github_api_base', ''), PHP_URL_HOST) ?: ''));
        if ($apiHost !== '') {
            $githubHosts[] = $apiHost;
        }
        $releaseApiHost = strtolower((string) (parse_url((string) config('version.release.api_base', ''), PHP_URL_HOST) ?: ''));
        if ($releaseApiHost !== '') {
            $githubHosts[] = $releaseApiHost;
        }

        $allowedHosts = array_values(array_unique(array_filter(array_merge(
            $githubHosts,
            array_map('strtolower', (array) config('version.release.allowed_hosts', []))
        ))));

        if (!in_array($host, $allowedHosts, true)) {
            throw new FailedException('发布地址不在允许的域名白名单内');
        }
    }

    private function isLocalFileUrl(string $url): bool
    {
        if (str_starts_with($url, 'file://')) {
            return true;
        }

        return is_file($url) || is_file(app()->getRootPath() . ltrim($url, '/\\'));
    }

    private function localPathFromUrl(string $url): string
    {
        if (str_starts_with($url, 'file://')) {
            $path = substr($url, 7);
        } elseif (is_file($url)) {
            $path = $url;
        } else {
            $path = app()->getRootPath() . ltrim($url, '/\\');
        }

        $realPath = realpath($path);
        if (!$realPath || !is_file($realPath)) {
            throw new FailedException('本地文件不存在');
        }

        return $realPath;
    }

    private function githubToken(): string
    {
        return trim((string) config('version.release.github_token', '')) ?: $this->releaseToken();
    }

    private function githubHeaders(bool $download = false): array
    {
        $headers = [
            'User-Agent: LightAdmin-Upgrader',
            'X-GitHub-Api-Version: 2022-11-28',
            'Accept: ' . ($download ? 'application/octet-stream' : 'application/vnd.github+json'),
        ];

        $token = $this->githubToken();
        if ($token !== '') {
            $headers[] = 'Authorization: Bearer ' . $token;
        }

        return $headers;
    }

    private function githubApiUrl(array $github, string $path): string
    {
        return rtrim((string) $github['api_base'], '/') . '/' . ltrim($path, '/');
    }

    private function platformApiUrl(array $platform, string $path): string
    {
        return rtrim((string) $platform['api_base'], '/') . '/' . ltrim($path, '/');
    }

    private function platformHeaders(array $platform): array
    {
        $headers = ['User-Agent: LightAdmin-Upgrader'];
        if (($platform['source'] ?? '') === 'cnb') {
            $headers[] = 'Accept: application/vnd.cnb.api+json';
        }

        $token = $this->releaseToken();
        if ($token === '') {
            return $headers;
        }

        $configured = trim((string) config('version.release.release_token_header', ''));
        $header = $configured !== '' ? $configured : match ($platform['source']) {
            'gitlab' => 'PRIVATE-TOKEN: {token}',
            'gitee' => 'Authorization: token {token}',
            default => 'Authorization: Bearer {token}',
        };

        $headers[] = str_replace('{token}', $token, $header);
        return $headers;
    }

    private function githubReleasePageUrl(array $github, string $tag): string
    {
        return 'https://github.com/' . $github['owner'] . '/' . $github['repo'] . '/releases/tag/' . rawurlencode($tag);
    }

    private function cnbRepoPath(array $platform): string
    {
        $project = trim((string) ($platform['project'] ?: trim($platform['owner'] . '/' . $platform['repo'], '/')), '/');
        $project = preg_replace('/\.git$/i', '', $project) ?: '';
        if ($project === '') {
            throw new FailedException('请先配置腾讯 CNB 仓库路径');
        }

        // CNB 把仓库路径直接放在 API 路径前缀里，所以只编码每个片段，不能把斜杠整体编码掉。
        return implode('/', array_map('rawurlencode', array_filter(explode('/', $project), fn(string $item) => $item !== '')));
    }

    private function cnbAssetDownloadUrl(array $platform, string $tag, string $assetName): string
    {
        return $this->platformApiUrl(
            $platform,
            $this->cnbRepoPath($platform) . '/-/releases/download/' . rawurlencode($tag) . '/' . rawurlencode($assetName)
        );
    }

    private function releasePageUrl(array $platform, string $tag): string
    {
        return match ($platform['source']) {
            'gitlab' => 'https://gitlab.com/' . trim($platform['project'], '/') . '/-/releases/' . rawurlencode($tag),
            'gitee' => 'https://gitee.com/' . $platform['owner'] . '/' . $platform['repo'] . '/releases/' . rawurlencode($tag),
            'cnb' => 'https://cnb.cool/' . $this->cnbRepoPath($platform) . '/-/releases/tag/' . rawurlencode($tag),
            default => $this->githubReleasePageUrl($platform, $tag),
        };
    }

    private function githubErrorMessage(string $content): string
    {
        $decoded = json_decode($content, true);
        return is_array($decoded) ? (string) ($decoded['message'] ?? $decoded['errmsg'] ?? $decoded['error'] ?? '') : '';
    }

    private function candidateTags(string $version): array
    {
        $version = trim($version);
        $normalized = $this->normalizeVersion($version);
        return array_values(array_unique(array_filter([
            $version,
            $normalized,
            $normalized !== '' ? 'v' . $normalized : '',
        ])));
    }

    private function findGitHubAsset(array $release, string $assetPattern, string $version, string $tag): array
    {
        return $this->findReleaseAsset($release, $assetPattern, $version, $tag);
    }

    private function findReleaseAsset(array $release, string $assetPattern, string $version, string $tag): array
    {
        $assets = $this->releaseAssets($release);
        if (!$assets) {
            throw new FailedException('Release 未上传升级包资源');
        }

        $expectedNames = $this->expectedAssetNames($assetPattern, $version, $tag);
        foreach ($expectedNames as $expectedName) {
            foreach ($assets as $asset) {
                if (strcasecmp((string) ($asset['name'] ?? ''), $expectedName) === 0) {
                    return $asset;
                }
            }
        }

        $zipAssets = array_values(array_filter($assets, fn(array $asset) => str_ends_with(strtolower((string) ($asset['name'] ?? '')), '.zip')));
        $matchedAssets = array_values(array_filter($zipAssets, function (array $asset) use ($version, $tag) {
            $name = strtolower((string) ($asset['name'] ?? ''));
            return str_contains($name, strtolower($version)) || ($tag !== '' && str_contains($name, strtolower($tag)));
        }));

        if (count($matchedAssets) === 1) {
            return $matchedAssets[0];
        }

        if (!$matchedAssets && count($zipAssets) === 1) {
            return $zipAssets[0];
        }

        throw new FailedException('Release 中未找到匹配的 zip 升级包：' . $assetPattern);
    }

    private function releaseAssets(array $release): array
    {
        $assets = [];
        foreach ((array) ($release['assets'] ?? []) as $key => $value) {
            if ($key === 'links' && is_array($value)) {
                foreach ($value as $asset) {
                    if (is_array($asset)) {
                        $assets[] = $asset;
                    }
                }
                continue;
            }

            if (is_array($value) && isset($value['name'])) {
                $assets[] = $value;
            }
        }

        foreach (['links', 'attach_files', 'files', 'attachments'] as $field) {
            foreach ((array) ($release[$field] ?? []) as $asset) {
                if (is_array($asset)) {
                    $assets[] = $asset;
                }
            }
        }

        return $assets;
    }

    private function assetDownloadUrl(array $asset): string
    {
        return (string) (
            $asset['browser_download_url']
            ?? $asset['brower_download_url']
            ?? $asset['direct_asset_url']
            ?? $asset['download_url']
            ?? $asset['url']
            ?? ''
        );
    }

    private function expectedAssetNames(string $assetPattern, string $version, string $tag): array
    {
        $assetPattern = $assetPattern !== '' ? $assetPattern : 'light-admin-{version}.zip';
        return array_values(array_unique(array_filter([
            strtr($assetPattern, ['{version}' => $version, '{tag}' => $tag]),
            strtr($assetPattern, ['{version}' => $tag, '{tag}' => $tag]),
        ])));
    }

    private function sha256FromDigest(string $digest): string
    {
        if (preg_match('/sha256[:=]([a-f0-9]{64})/i', $digest, $matches)) {
            return strtolower($matches[1]);
        }

        return preg_match('/^[a-f0-9]{64}$/i', $digest) ? strtolower($digest) : '';
    }

    private function sha256FromChecksumAsset(array $release, array $packageAsset): string
    {
        $packageName = (string) ($packageAsset['name'] ?? '');
        if ($packageName === '') {
            return '';
        }

        $baseName = preg_replace('/\.zip$/i', '', $packageName) ?: $packageName;
        $candidateNames = array_map('strtolower', [
            $packageName . '.sha256',
            $baseName . '.sha256',
            'checksums.sha256',
            'sha256sums.txt',
            'SHA256SUMS',
        ]);

        foreach ($this->releaseAssets($release) as $asset) {
            $name = strtolower((string) ($asset['name'] ?? ''));
            if (!in_array($name, $candidateNames, true)) {
                continue;
            }

            $useApiUrl = (string) ($asset['browser_download_url'] ?? '') !== '' && (string) ($asset['url'] ?? '') !== '' && $this->githubToken() !== '';
            $url = $useApiUrl ? (string) $asset['url'] : $this->assetDownloadUrl($asset);
            if ($url === '') {
                continue;
            }

            $content = $this->readRemoteContent($url, $useApiUrl ? $this->githubHeaders(true) : []);
            $sha256 = $this->parseChecksumContent($content, $packageName);
            if ($sha256 !== '') {
                return $sha256;
            }
        }

        return '';
    }

    private function parseChecksumContent(string $content, string $packageName): string
    {
        $fallback = '';
        foreach (preg_split('/\r\n|\r|\n/', $content) ?: [] as $line) {
            if (!preg_match('/\b([a-f0-9]{64})\b/i', $line, $matches)) {
                continue;
            }

            $hash = strtolower($matches[1]);
            if ($fallback === '') {
                $fallback = $hash;
            }

            if ($packageName === '' || str_contains($line, $packageName)) {
                return $hash;
            }
        }

        return $fallback;
    }

    private function releaseMetadataFromBody(string $body): array
    {
        $metadata = [];
        foreach (['/<!--\s*upgrade\s*(.*?)-->/is', '/```upgrade\s*(.*?)```/is'] as $pattern) {
            if (!preg_match($pattern, $body, $matches)) {
                continue;
            }

            foreach (preg_split('/\r\n|\r|\n/', trim((string) $matches[1])) ?: [] as $line) {
                if (preg_match('/^\s*([A-Za-z0-9_]+)\s*[:=]\s*(.+?)\s*$/', $line, $item)) {
                    $metadata[$item[1]] = trim($item[2], " \t\n\r\0\x0B\"'");
                }
            }
        }

        return $metadata;
    }

    private function releaseNotesFromBody(string $body): array
    {
        $body = preg_replace('/<!--\s*upgrade\s*.*?-->/is', '', $body) ?? $body;
        $body = preg_replace('/```upgrade\s*.*?```/is', '', $body) ?? $body;
        $notes = [];

        foreach (preg_split('/\r\n|\r|\n/', $body) ?: [] as $line) {
            $line = trim(preg_replace('/^\s*(#{1,6}|[-*+]|\d+\.)\s*/', '', $line) ?? $line);
            $line = trim(preg_replace('/^\[[ xX]\]\s*/', '', $line) ?? $line);
            if ($line === '') {
                continue;
            }

            $notes[] = $line;
            if (count($notes) >= 12) {
                break;
            }
        }

        return $notes;
    }

    private function booleanValue(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? (bool) $value;
    }

    private function normalizeVersionEntry(array $version): array
    {
        $version['version'] = (string) ($version['version'] ?? '');
        $version['tag_name'] = (string) ($version['tag_name'] ?? '');
        $version['build'] = (string) ($version['build'] ?? '');
        $version['released_at'] = (string) ($version['released_at'] ?? '');
        $version['channel'] = (string) ($version['channel'] ?? config('version.release.channel', 'stable'));
        $version['package_url'] = (string) ($version['package_url'] ?? '');
        $version['asset_api_url'] = (string) ($version['asset_api_url'] ?? '');
        $version['asset_name'] = (string) ($version['asset_name'] ?? '');
        $version['sha256'] = strtolower((string) ($version['sha256'] ?? ''));
        $version['release_url'] = (string) ($version['release_url'] ?? '');
        $version['source'] = (string) ($version['source'] ?? config('version.release.source', 'github'));
        $version['required'] = (bool) ($version['required'] ?? false);
        $version['database_migration'] = (bool) ($version['database_migration'] ?? false);
        $version['release_notes'] = array_values((array) ($version['release_notes'] ?? []));
        return $version;
    }

    private function normalizeVersion(string $version): string
    {
        return ltrim(trim($version), 'vV');
    }

    private function appendCheck(array &$checks, string $key, string $title, bool $passed, string $message, string $status = ''): void
    {
        $checks[] = [
            'key' => $key,
            'title' => $title,
            'status' => $status ?: ($passed ? 'pass' : 'fail'),
            'message' => $message,
        ];
    }

    private function matchPhpConstraint(string $constraint): bool
    {
        $parts = array_filter(array_map('trim', explode(',', $constraint)));
        foreach ($parts as $part) {
            if (!preg_match('/^(>=|<=|>|<|=)?\s*(.+)$/', $part, $matches)) {
                continue;
            }

            $operator = $matches[1] ?: '>=';
            $version = $matches[2];
            if (!version_compare(PHP_VERSION, $version, $operator)) {
                return false;
            }
        }

        return true;
    }

    private function ensureDirectory(string $path): bool
    {
        return is_dir($path) || @mkdir($path, 0777, true);
    }

    private function safePackageFileName(string $version, string $assetName = ''): string
    {
        if ($assetName !== '' && preg_match('/^[A-Za-z0-9._-]+\.zip$/', $assetName)) {
            return $assetName;
        }

        $safeVersion = preg_replace('/[^A-Za-z0-9._-]/', '_', $version) ?: 'latest';
        return 'light-admin-' . $safeVersion . '.zip';
    }

    private function absolutePath(string $path): string
    {
        if (preg_match('/^[A-Za-z]:[\\\\\\/]/', $path) || str_starts_with($path, '/')) {
            return rtrim($path, DIRECTORY_SEPARATOR);
        }

        return rtrim(app()->getRootPath() . ltrim($path, '/\\'), DIRECTORY_SEPARATOR);
    }

    private function currentUserId(): int
    {
        try {
            return (int) request()->uid();
        } catch (\Throwable) {
            return 0;
        }
    }
}
