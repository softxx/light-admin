<?php

namespace app\service\system;

use app\model\system\SystemVersion;
use app\model\system\UpgradeTask;
use core\base\BaseService;
use core\exception\FailedException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use think\console\Output;
use think\facade\Db;
use ZipArchive;

/**
 * 升级执行服务。
 *
 * 这里运行在 CLI 场景下，负责完整升级链路：加锁、下载/校验、备份、维护模式、
 * 文件替换、数据库迁移、收尾和失败回滚。
 */
class UpgradeService extends BaseService
{
    // 终态任务不会再被视为运行中任务。
    private const FINAL_STATUSES = ['success', 'failed', 'rolled_back', 'rollback_failed'];

    // 升级包 backend 目录中允许覆盖到项目根目录的条目。
    private const BACKEND_ENTRIES = [
        'app',
        'config',
        'core',
        'public',
        'route',
        'vendor',
        'composer.json',
        'composer.lock',
        'think',
        'VERSION',
    ];

    public function launchTask(int $taskId): void
    {
        $this->setTaskState($taskId, 'queued', 1, '升级任务已进入队列');
        $this->launchCommand($taskId, false);
    }

    public function launchRollback(int $taskId): void
    {
        $this->setTaskState($taskId, 'queued', 1, '回滚任务已进入队列');
        $this->launchCommand($taskId, true);
    }

    /**
     * 执行升级任务主流程。
     */
    public function runTask(int $taskId, ?Output $output = null): void
    {
        $task = UpgradeTask::find($taskId);
        if (!$task) {
            throw new FailedException('升级任务不存在');
        }

        $versionService = app()->make(VersionService::class);
        $version = $this->decodeJson((string) $task->manifest);
        $packagePath = (string) $task->package_path;
        $extractDir = '';
        $backupPath = '';

        try {
            // 升级锁必须最先创建，避免两个任务同时替换文件。
            $this->acquireLock($taskId);
            $this->setTaskState($taskId, 'downloading', 8, '准备升级包', true);

            // 如果任务里已有本地包则复用，否则按 GitHub Release 元数据下载。
            if ($packagePath === '' || !is_file($packagePath)) {
                $download = $versionService->downloadPackage($version);
                $packagePath = (string) $download['package_path'];
                $this->updateTask($taskId, ['package_path' => $packagePath]);
                $this->appendLog($taskId, 'info', '升级包下载完成：' . $packagePath, $output);
            } else {
                $this->assertPackagePath($packagePath);
                $versionService->verifyPackage($packagePath, $version);
                $this->appendLog($taskId, 'info', '使用已下载的升级包：' . $packagePath, $output);
            }

            $this->setTaskState($taskId, 'verifying', 18, '升级包校验完成');

            $precheck = $versionService->runPrecheck($version, $packagePath);
            $this->updateTask($taskId, [
                'precheck' => json_encode($precheck, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]);
            if (!$precheck['can_upgrade']) {
                throw new FailedException('预检查未通过');
            }

            $this->setTaskState($taskId, 'prechecking', 26, '预检查通过');
            $extractDir = $this->extractPackage($taskId, $packagePath);
            $this->runPackageScript($extractDir, 'precheck.php', $taskId, $version);

            // 先备份再进入维护模式，确保失败后有可恢复的文件和数据库快照。
            $this->setTaskState($taskId, 'backing_up', 36, '正在备份当前版本');
            $backupPath = $this->backupCurrentVersion($taskId, $extractDir);
            $this->updateTask($taskId, ['backup_path' => $backupPath]);

            $this->setTaskState($taskId, 'maintenance', 46, '系统进入维护模式');
            $this->enableMaintenance($taskId);

            // 维护模式后才开始文件替换和数据库迁移。
            $this->setTaskState($taskId, 'installing', 60, '正在替换程序文件');
            $this->installPackage($extractDir);

            $this->setTaskState($taskId, 'migrating', 76, '正在执行数据库迁移');
            $this->executeMigrations($extractDir, false);

            $this->setTaskState($taskId, 'finishing', 90, '正在执行升级后处理');
            $this->runPackageScript($extractDir, 'post_upgrade.php', $taskId, $version);
            $this->clearRuntimeCache();
            $this->recordInstalledVersion($version);

            $this->disableMaintenance();
            $this->releaseLock($taskId);
            $this->setTaskState($taskId, 'success', 100, '升级完成', false, true);
        } catch (\Throwable $e) {
            $this->appendLog($taskId, 'error', $e->getMessage(), $output);

            // 文件或迁移失败后优先尝试自动回滚，避免系统停留在半升级状态。
            if ($backupPath !== '') {
                try {
                    $this->appendLog($taskId, 'info', '开始自动回滚', $output);
                    $this->restoreBackup($backupPath);
                    $this->appendLog($taskId, 'info', '自动回滚完成', $output);
                } catch (\Throwable $rollbackError) {
                    $this->appendLog($taskId, 'error', '自动回滚失败：' . $rollbackError->getMessage(), $output);
                }
            }

            $this->disableMaintenance();
            $this->releaseLock($taskId);
            $this->setTaskState($taskId, 'failed', 100, '升级失败', false, true, $e->getMessage());
        } finally {
            if ($extractDir !== '') {
                $this->removePath($extractDir);
            }
        }
    }

    /**
     * 执行指定任务的回滚流程。
     */
    public function runRollback(int $taskId, ?Output $output = null): void
    {
        $task = UpgradeTask::find($taskId);
        if (!$task) {
            throw new FailedException('升级任务不存在');
        }

        $backupPath = (string) $task->backup_path;
        if ($backupPath === '' || !is_dir($backupPath)) {
            throw new FailedException('备份目录不存在，无法回滚');
        }

        try {
            $this->acquireLock($taskId);
            $this->setTaskState($taskId, 'rolling_back', 15, '系统进入回滚流程', true);
            $this->enableMaintenance($taskId);
            $this->restoreBackup($backupPath);
            $this->clearRuntimeCache();
            $this->disableMaintenance();
            $this->releaseLock($taskId);
            $this->setTaskState($taskId, 'rolled_back', 100, '回滚完成', false, true);
        } catch (\Throwable $e) {
            $this->appendLog($taskId, 'error', $e->getMessage(), $output);
            $this->disableMaintenance();
            $this->releaseLock($taskId);
            $this->setTaskState($taskId, 'rollback_failed', 100, '回滚失败', false, true, $e->getMessage());
        }
    }

    /**
     * 从后台请求中拉起 CLI 子进程。
     */
    private function launchCommand(int $taskId, bool $rollback): void
    {
        if (!VersionService::canLaunchBackgroundProcess()) {
            $this->setTaskState($taskId, 'failed', 100, '当前环境无法拉起后台任务', false, true, 'popen/proc_open 不可用');
            throw new FailedException('当前环境无法拉起后台任务，请检查 popen/proc_open 是否被禁用');
        }

        $rootPath = app()->getRootPath();
        $php = PHP_BINARY;
        $think = $rootPath . 'think';
        $logDir = app()->getRuntimePath() . 'upgrade_process';
        $this->ensureDirectory($logDir);
        $logFile = $logDir . DIRECTORY_SEPARATOR . 'task-' . $taskId . '.log';

        $command = $this->quoteArg($php) . ' ' . $this->quoteArg($think) . ' system:upgrade --task ' . $taskId;
        if ($rollback) {
            $command .= ' --rollback';
        }

        // Windows 和 Linux/macOS 的后台启动语法不同，这里分别处理。
        if (PHP_OS_FAMILY === 'Windows') {
            $backgroundCommand = 'start /B "" ' . $command . ' > ' . $this->quoteArg($logFile) . ' 2>&1';
        } else {
            $backgroundCommand = $command . ' > ' . $this->quoteArg($logFile) . ' 2>&1 &';
        }

        if (VersionService::functionAvailable('popen')) {
            $handle = @popen($backgroundCommand, 'r');
            if (!$handle) {
                $this->setTaskState($taskId, 'failed', 100, '启动升级任务失败', false, true);
                throw new FailedException('启动升级任务失败');
            }
            pclose($handle);
            return;
        }

        $descriptor = [
            0 => ['pipe', 'r'],
            1 => ['file', $logFile, 'a'],
            2 => ['file', $logFile, 'a'],
        ];
        $process = @proc_open($command, $descriptor, $pipes, $rootPath);
        if (!is_resource($process)) {
            $this->setTaskState($taskId, 'failed', 100, '启动升级任务失败', false, true);
            throw new FailedException('启动升级任务失败');
        }

        foreach ($pipes as $pipe) {
            if (is_resource($pipe)) {
                fclose($pipe);
            }
        }
    }

    /**
     * 创建升级锁，锁文件中记录任务 ID。
     */
    private function acquireLock(int $taskId): void
    {
        $versionService = app()->make(VersionService::class);
        $lockPath = $versionService->upgradeLockPath();
        $this->ensureDirectory(dirname($lockPath));

        if (is_file($lockPath)) {
            $payload = json_decode((string) file_get_contents($lockPath), true);
            $lockedTaskId = (int) ($payload['task_id'] ?? 0);
            if ($lockedTaskId !== $taskId) {
                throw new FailedException('已有升级任务正在执行');
            }
        }

        file_put_contents($lockPath, json_encode([
            'task_id' => $taskId,
            'locked_at' => time(),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function releaseLock(int $taskId): void
    {
        $versionService = app()->make(VersionService::class);
        $lockPath = $versionService->upgradeLockPath();
        if (!is_file($lockPath)) {
            return;
        }

        $payload = json_decode((string) file_get_contents($lockPath), true);
        if ((int) ($payload['task_id'] ?? 0) === $taskId) {
            @unlink($lockPath);
        }
    }

    /**
     * 写入维护模式锁，普通接口会被维护模式中间件拦截。
     */
    private function enableMaintenance(int $taskId): void
    {
        $versionService = app()->make(VersionService::class);
        $lockPath = $versionService->maintenanceLockPath();
        $this->ensureDirectory(dirname($lockPath));
        file_put_contents($lockPath, json_encode([
            'task_id' => $taskId,
            'message' => '系统正在升级维护',
            'started_at' => time(),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function disableMaintenance(): void
    {
        $versionService = app()->make(VersionService::class);
        $lockPath = $versionService->maintenanceLockPath();
        if (is_file($lockPath)) {
            @unlink($lockPath);
        }
    }

    /**
     * 安全解压升级包，禁止绝对路径和 ../ 路径穿越。
     */
    private function extractPackage(int $taskId, string $packagePath): string
    {
        if (!class_exists(ZipArchive::class)) {
            throw new FailedException('当前 PHP 缺少 ZipArchive 扩展');
        }

        $versionService = app()->make(VersionService::class);
        $extractDir = $versionService->workDir('extract' . DIRECTORY_SEPARATOR . 'task-' . $taskId . '-' . time());
        $this->ensureDirectory($extractDir);

        $zip = new ZipArchive();
        if ($zip->open($packagePath) !== true) {
            throw new FailedException('升级包无法打开');
        }

        for ($index = 0; $index < $zip->numFiles; $index++) {
            $name = (string) $zip->getNameIndex($index);
            $normalized = str_replace('\\', '/', $name);
            if (str_starts_with($normalized, '/') || str_contains($normalized, '../') || preg_match('/^[A-Za-z]:\//', $normalized)) {
                $zip->close();
                throw new FailedException('升级包包含非法路径：' . $name);
            }
        }

        if (!$zip->extractTo($extractDir)) {
            $zip->close();
            throw new FailedException('升级包解压失败');
        }

        $zip->close();
        $this->appendLog($taskId, 'info', '升级包已解压');
        return $extractDir;
    }

    /**
     * 备份即将被升级包覆盖的文件；存在迁移脚本时同时备份数据库。
     */
    private function backupCurrentVersion(int $taskId, string $extractDir): string
    {
        $versionService = app()->make(VersionService::class);
        $backupDir = $versionService->workDir('backups' . DIRECTORY_SEPARATOR . date('YmdHis') . '-task-' . $taskId);
        $this->ensureDirectory($backupDir);

        $metadata = [
            'created_at' => date('Y-m-d H:i:s'),
            'root_path' => app()->getRootPath(),
            'files' => [],
            'database' => '',
        ];

        $backendDir = $extractDir . DIRECTORY_SEPARATOR . 'backend';
        if (is_dir($backendDir)) {
            foreach (self::BACKEND_ENTRIES as $entry) {
                $source = $backendDir . DIRECTORY_SEPARATOR . $entry;
                if (!file_exists($source)) {
                    continue;
                }

                $destination = app()->getRootPath() . $entry;
                $backupTarget = $backupDir . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . $entry;
                $excludes = $entry === 'public' ? ['storage'] : [];
                $existed = file_exists($destination);

                if ($existed) {
                    $this->copyPath($destination, $backupTarget, $excludes);
                }

                $metadata['files'][] = [
                    'entry' => $entry,
                    'destination' => $destination,
                    'backup' => $backupTarget,
                    'existed' => $existed,
                    'excludes' => $excludes,
                ];
            }
        }

        $frontendDist = $extractDir . DIRECTORY_SEPARATOR . 'frontend' . DIRECTORY_SEPARATOR . 'dist';
        if (is_dir($frontendDist)) {
            foreach ($this->listDirectory($frontendDist) as $source) {
                $entry = basename($source);
                $destination = app()->getRootPath() . 'public' . DIRECTORY_SEPARATOR . $entry;
                $backupTarget = $backupDir . DIRECTORY_SEPARATOR . 'frontend' . DIRECTORY_SEPARATOR . $entry;
                $existed = file_exists($destination);

                if ($existed) {
                    $this->copyPath($destination, $backupTarget);
                }

                $metadata['files'][] = [
                    'entry' => 'public/' . $entry,
                    'destination' => $destination,
                    'backup' => $backupTarget,
                    'existed' => $existed,
                    'excludes' => [],
                ];
            }
        }

        if ($this->hasMigrations($extractDir)) {
            $metadata['database'] = $this->backupDatabase($backupDir);
        }

        file_put_contents(
            $backupDir . DIRECTORY_SEPARATOR . 'metadata.json',
            json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
        );

        $this->appendLog($taskId, 'info', '当前版本备份完成：' . $backupDir);
        return $backupDir;
    }

    /**
     * 将升级包内 backend 和 frontend/dist 内容覆盖到当前项目。
     */
    private function installPackage(string $extractDir): void
    {
        $backendDir = $extractDir . DIRECTORY_SEPARATOR . 'backend';
        $frontendDist = $extractDir . DIRECTORY_SEPARATOR . 'frontend' . DIRECTORY_SEPARATOR . 'dist';
        $hasInstallableContent = false;

        if (is_dir($backendDir)) {
            foreach (self::BACKEND_ENTRIES as $entry) {
                $source = $backendDir . DIRECTORY_SEPARATOR . $entry;
                if (!file_exists($source)) {
                    continue;
                }

                $hasInstallableContent = true;
                $destination = app()->getRootPath() . $entry;
                if ($entry === 'public' && is_dir($source)) {
                    $this->installPublicDirectory($source, $destination);
                    continue;
                }

                $this->removePath($destination);
                $this->copyPath($source, $destination);
            }
        }

        if (is_dir($frontendDist)) {
            $hasInstallableContent = true;
            $publicDir = app()->getRootPath() . 'public';
            foreach ($this->listDirectory($frontendDist) as $source) {
                $destination = $publicDir . DIRECTORY_SEPARATOR . basename($source);
                $this->removePath($destination);
                $this->copyPath($source, $destination);
            }
        }

        if (!$hasInstallableContent) {
            throw new FailedException('升级包缺少 backend 或 frontend/dist 内容');
        }
    }

    private function installPublicDirectory(string $source, string $destination): void
    {
        $this->ensureDirectory($destination);
        foreach ($this->listDirectory($source) as $item) {
            if (basename($item) === 'storage') {
                continue;
            }

            $target = $destination . DIRECTORY_SEPARATOR . basename($item);
            $this->removePath($target);
            $this->copyPath($item, $target);
        }
    }

    /**
     * 执行升级包 migrations 目录下的 SQL 脚本。
     */
    private function executeMigrations(string $extractDir, bool $rollback): void
    {
        $migrationDir = $extractDir . DIRECTORY_SEPARATOR . 'migrations';
        if (!is_dir($migrationDir)) {
            return;
        }

        $files = glob($migrationDir . DIRECTORY_SEPARATOR . '*.sql') ?: [];
        $files = array_values(array_filter($files, function (string $file) use ($rollback) {
            $isRollback = str_starts_with(basename($file), 'rollback_');
            return $rollback ? $isRollback : !$isRollback;
        }));
        sort($files);

        foreach ($files as $file) {
            $this->executeSqlFile($file);
        }
    }

    private function executeSqlFile(string $file): void
    {
        $sql = (string) file_get_contents($file);
        $sql = preg_replace('/^\s*--.*$/m', '', $sql) ?? $sql;
        $statements = $this->splitSqlStatements($sql);

        Db::execute('SET FOREIGN_KEY_CHECKS = 0');
        try {
            foreach ($statements as $statement) {
                $statement = trim($statement);
                if ($statement === '') {
                    continue;
                }
                Db::execute($statement);
            }
        } finally {
            Db::execute('SET FOREIGN_KEY_CHECKS = 1');
        }
    }

    private function splitSqlStatements(string $sql): array
    {
        $statements = [];
        $buffer = '';
        $inSingle = false;
        $inDouble = false;
        $escaped = false;
        $length = strlen($sql);

        for ($i = 0; $i < $length; $i++) {
            $char = $sql[$i];
            $buffer .= $char;

            if ($escaped) {
                $escaped = false;
                continue;
            }

            if ($char === '\\') {
                $escaped = true;
                continue;
            }

            if ($char === "'" && !$inDouble) {
                $inSingle = !$inSingle;
                continue;
            }

            if ($char === '"' && !$inSingle) {
                $inDouble = !$inDouble;
                continue;
            }

            if ($char === ';' && !$inSingle && !$inDouble) {
                $statements[] = substr($buffer, 0, -1);
                $buffer = '';
            }
        }

        if (trim($buffer) !== '') {
            $statements[] = $buffer;
        }

        return $statements;
    }

    /**
     * 根据 metadata.json 恢复文件和数据库备份。
     */
    private function restoreBackup(string $backupDir): void
    {
        $metadataPath = $backupDir . DIRECTORY_SEPARATOR . 'metadata.json';
        if (!is_file($metadataPath)) {
            throw new FailedException('备份元数据不存在');
        }

        $metadata = json_decode((string) file_get_contents($metadataPath), true);
        if (!is_array($metadata)) {
            throw new FailedException('备份元数据无效');
        }

        $files = array_reverse((array) ($metadata['files'] ?? []));
        foreach ($files as $file) {
            $destination = (string) ($file['destination'] ?? '');
            $backup = (string) ($file['backup'] ?? '');
            $existed = (bool) ($file['existed'] ?? false);
            $excludes = (array) ($file['excludes'] ?? []);

            if ($destination === '') {
                continue;
            }

            $this->removePath($destination, $excludes);
            if ($existed && file_exists($backup)) {
                $this->copyPath($backup, $destination, $excludes);
            }
        }

        $databaseBackup = (string) ($metadata['database'] ?? '');
        if ($databaseBackup !== '' && is_file($databaseBackup)) {
            $this->executeSqlFile($databaseBackup);
        }
    }

    /**
     * 生成简单 SQL 备份文件，供升级失败时回滚使用。
     */
    private function backupDatabase(string $backupDir): string
    {
        $target = $backupDir . DIRECTORY_SEPARATOR . 'database.sql';
        $handle = fopen($target, 'wb');
        if (!$handle) {
            throw new FailedException('无法创建数据库备份文件');
        }

        fwrite($handle, "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS = 0;\n\n");
        $tables = Db::query('SHOW TABLES');

        foreach ($tables as $row) {
            $table = (string) array_values($row)[0];
            $createRows = Db::query('SHOW CREATE TABLE `' . str_replace('`', '``', $table) . '`');
            $createSql = (string) ($createRows[0]['Create Table'] ?? array_values($createRows[0])[1] ?? '');
            fwrite($handle, 'DROP TABLE IF EXISTS `' . str_replace('`', '``', $table) . "`;\n");
            fwrite($handle, $createSql . ";\n\n");

            $dataRows = Db::query('SELECT * FROM `' . str_replace('`', '``', $table) . '`');
            foreach ($dataRows as $dataRow) {
                $columns = array_map(fn($column) => '`' . str_replace('`', '``', (string) $column) . '`', array_keys($dataRow));
                $values = array_map(fn($value) => $this->quoteSqlValue($value), array_values($dataRow));
                fwrite($handle, 'INSERT INTO `' . str_replace('`', '``', $table) . '` (' . implode(',', $columns) . ') VALUES (' . implode(',', $values) . ");\n");
            }
            fwrite($handle, "\n");
        }

        fwrite($handle, "SET FOREIGN_KEY_CHECKS = 1;\n");
        fclose($handle);

        return $target;
    }

    private function quoteSqlValue(mixed $value): string
    {
        if ($value === null) {
            return 'NULL';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return "'" . str_replace("'", "''", (string) $value) . "'";
    }

    /**
     * 执行升级包中的受控 PHP 脚本，脚本可读取 $context。
     */
    private function runPackageScript(string $extractDir, string $scriptName, int $taskId, array $version): void
    {
        $script = $extractDir . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . $scriptName;
        if (!is_file($script)) {
            return;
        }

        $context = [
            'task_id' => $taskId,
            'version' => $version,
            'root_path' => app()->getRootPath(),
            'runtime_path' => app()->getRuntimePath(),
            'package_path' => $extractDir,
        ];

        $result = include $script;
        if ($result === false) {
            throw new FailedException('升级脚本执行失败：' . $scriptName);
        }
    }

    private function clearRuntimeCache(): void
    {
        $cacheDir = app()->getRuntimePath() . 'cache';
        if (is_dir($cacheDir)) {
            $this->removePath($cacheDir);
        }
    }

    private function recordInstalledVersion(array $version): void
    {
        SystemVersion::create([
            'version' => (string) ($version['version'] ?? ''),
            'build' => (string) ($version['build'] ?? ''),
            'commit_hash' => (string) ($version['commit'] ?? ''),
            'channel' => (string) ($version['channel'] ?? config('version.release.channel', 'stable')),
            'release_notes' => json_encode($version['release_notes'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'installed_at' => time(),
        ]);
    }

    private function hasMigrations(string $extractDir): bool
    {
        $migrationDir = $extractDir . DIRECTORY_SEPARATOR . 'migrations';
        return is_dir($migrationDir) && (bool) glob($migrationDir . DIRECTORY_SEPARATOR . '*.sql');
    }

    private function listDirectory(string $path): array
    {
        $items = [];
        $children = scandir($path);
        if ($children === false) {
            return [];
        }

        foreach ($children as $child) {
            if ($child === '.' || $child === '..') {
                continue;
            }
            $items[] = $path . DIRECTORY_SEPARATOR . $child;
        }

        return $items;
    }

    private function copyPath(string $source, string $destination, array $excludeNames = []): void
    {
        if (is_file($source)) {
            $this->ensureDirectory(dirname($destination));
            if (!@copy($source, $destination)) {
                throw new FailedException('复制文件失败：' . $source);
            }
            return;
        }

        if (!is_dir($source)) {
            return;
        }

        $this->ensureDirectory($destination);
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $relativePath = substr($item->getPathname(), strlen($source) + 1);
            $segments = preg_split('/[\\\\\\/]+/', $relativePath) ?: [];
            if ($segments && in_array($segments[0], $excludeNames, true)) {
                continue;
            }

            $target = $destination . DIRECTORY_SEPARATOR . $relativePath;
            if ($item->isDir()) {
                $this->ensureDirectory($target);
                continue;
            }

            $this->ensureDirectory(dirname($target));
            if (!@copy($item->getPathname(), $target)) {
                throw new FailedException('复制文件失败：' . $item->getPathname());
            }
        }
    }

    private function removePath(string $path, array $excludeNames = []): void
    {
        if (!file_exists($path)) {
            return;
        }

        if (is_file($path) || is_link($path)) {
            @unlink($path);
            return;
        }

        $items = $this->listDirectory($path);
        foreach ($items as $item) {
            if (in_array(basename($item), $excludeNames, true)) {
                continue;
            }
            $this->removePath($item);
        }

        if (!$excludeNames) {
            @rmdir($path);
        }
    }

    private function assertPackagePath(string $path): void
    {
        $versionService = app()->make(VersionService::class);
        $packageDir = realpath($versionService->workDir('packages'));
        $packagePath = realpath($path);
        if (!$packageDir || !$packagePath || !str_starts_with($packagePath, $packageDir)) {
            throw new FailedException('升级包路径不在允许目录内');
        }
    }

    private function ensureDirectory(string $path): void
    {
        if (!is_dir($path) && !@mkdir($path, 0777, true)) {
            throw new FailedException('目录创建失败：' . $path);
        }
    }

    private function setTaskState(
        int $taskId,
        string $status,
        int $progress,
        string $message,
        bool $markStarted = false,
        bool $markFinished = false,
        string $error = ''
    ): void {
        $payload = [
            'status' => $status,
            'progress' => max(0, min(100, $progress)),
            'message' => $message,
        ];

        if ($markStarted) {
            $payload['started_at'] = time();
        }

        if ($markFinished) {
            $payload['finished_at'] = time();
        }

        if ($error !== '') {
            $payload['error'] = $error;
        }

        $this->updateTask($taskId, $payload);
        $level = in_array($status, ['failed', 'rollback_failed'], true) ? 'error' : 'info';
        $this->appendLog($taskId, $level, $message);
    }

    private function updateTask(int $taskId, array $payload): void
    {
        UpgradeTask::update($payload, ['id' => $taskId]);
    }

    private function appendLog(int $taskId, string $level, string $message, ?Output $output = null): void
    {
        $task = UpgradeTask::find($taskId);
        if (!$task) {
            return;
        }

        $logs = $this->decodeJson((string) $task->logs);
        $logs[] = [
            'time' => date('Y-m-d H:i:s'),
            'level' => $level,
            'message' => $message,
        ];

        if (count($logs) > 300) {
            $logs = array_slice($logs, -300);
        }

        UpgradeTask::update([
            'logs' => json_encode($logs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ], ['id' => $taskId]);

        if ($output) {
            $output->writeln('[' . $level . '] ' . $message);
        }
    }

    private function decodeJson(string $json): array
    {
        if ($json === '') {
            return [];
        }

        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function quoteArg(string $arg): string
    {
        return escapeshellarg($arg);
    }
}
