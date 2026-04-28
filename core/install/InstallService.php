<?php

declare(strict_types=1);

namespace core\install;

use core\exception\FailedException;
use PDO;
use Throwable;

class InstallService
{
    public function __construct(
        private readonly InstallStateStore $stateStore,
        private readonly InstallGuard $guard,
        private readonly EnvironmentChecker $environmentChecker
    ) {
    }

    public function getBootstrapPayload(): array
    {
        $databaseDefaults = $this->getDatabaseDefaults();
        $databaseDefaults['password'] = '';

        return [
            'environment' => $this->environmentChecker->check(),
            'database' => $databaseDefaults,
            'links' => $this->getLinks(),
        ];
    }

    public function getDatabaseDefaults(): array
    {
        $config = (array) config('database.connections.mysql', []);
        $charset = strtolower(trim((string) ($config['charset'] ?? 'utf8mb4')));
        if ($charset === '' || $charset === 'utf8') {
            $charset = 'utf8mb4';
        }

        return [
            'hostname' => (string) ($config['hostname'] ?? '127.0.0.1'),
            'hostport' => (string) ($config['hostport'] ?? '3306'),
            'database' => (string) ($config['database'] ?? ''),
            'username' => (string) ($config['username'] ?? ''),
            'password' => (string) ($config['password'] ?? ''),
            'charset' => $charset,
            'prefix' => (string) ($config['prefix'] ?? config('install.default_table_prefix', 'light_')),
        ];
    }

    public function checkDatabase(array $payload): array
    {
        $config = $this->normalizeDatabasePayload($payload);
        $serverPdo = $this->createServerPdo($config);
        $exists = $this->databaseExists($serverPdo, $config['database']);
        $empty = true;

        if ($exists) {
            $databasePdo = $this->createDatabasePdo($config);
            $empty = $this->isDatabaseEmpty($databasePdo, $config['database']);
        }

        return [
            'exists' => $exists,
            'empty' => $empty,
            'can_install' => !$exists || $empty,
            'message' => !$exists
                ? '数据库不存在，安装时会尝试自动创建'
                : ($empty ? '数据库为空，可以继续安装' : '数据库中已存在数据，Web 安装器不会覆盖已有库'),
            'database' => $config['database'],
            'prefix' => $config['prefix'],
        ];
    }

    public function install(array $payload): array
    {
        $this->environmentChecker->assertPasses();

        return $this->stateStore->withMutex(function () use ($payload) {
            $this->guard->ensureNotInstalled();

            $config = $this->normalizeDatabasePayload($payload);
            $databaseCheck = $this->checkDatabase($config);
            if (!$databaseCheck['can_install']) {
                throw new FailedException('目标数据库不是空库，安装器已拒绝执行覆盖安装');
            }

            $databaseCreated = false;
            $databaseExisted = $databaseCheck['exists'];

            $this->stateStore->mergeState([
                'status' => 'installing',
                'cleanup_status' => 'pending',
                'cleanup_required' => true,
                'db_host' => $config['hostname'],
                'db_name' => $config['database'],
                'table_prefix' => $config['prefix'],
                'links' => $this->getLinks(),
            ]);

            try {
                if (!$databaseExisted) {
                    $this->createDatabase($config);
                    $databaseCreated = true;
                }

                $this->writeEnvironmentFile($config);
                $databasePdo = $this->createDatabasePdo($config);
                $this->executeSqlFile($databasePdo, $config['prefix']);
                $this->verifyInstallation($databasePdo, $config['prefix']);

                $cleanupToken = $this->stateStore->issueCleanupToken();
                $installedAt = date('Y-m-d H:i:s');

                $this->stateStore->writeLock([
                    'db_name' => $config['database'],
                    'table_prefix' => $config['prefix'],
                ]);
                $this->stateStore->mergeState([
                    'status' => 'installed',
                    'cleanup_status' => 'pending',
                    'cleanup_required' => true,
                    'installed_at' => $installedAt,
                    'app_version' => (string) app()->version(),
                    'db_host' => $config['hostname'],
                    'db_name' => $config['database'],
                    'table_prefix' => $config['prefix'],
                    'links' => $this->getLinks(),
                ]);

                return [
                    'installed_at' => $installedAt,
                    'admin' => [
                        'username' => (string) config('install.admin_username', 'admin'),
                        'password' => (string) config('install.admin_password', '123456'),
                    ],
                    'links' => $this->getLinks(),
                    'cleanup_token' => $cleanupToken,
                ];
            } catch (Throwable $exception) {
                $this->rollbackFailedInstall($config, $databaseCreated, $databaseExisted);
                $this->stateStore->mergeState([
                    'status' => 'install_failed',
                    'cleanup_status' => 'pending',
                    'cleanup_required' => false,
                ]);

                throw $this->normalizeException($exception);
            }
        });
    }

    public function installFromCurrentConfig(): array
    {
        return $this->install($this->getDatabaseDefaults());
    }

    private function normalizeDatabasePayload(array $payload): array
    {
        $defaults = $this->getDatabaseDefaults();

        $hostname = trim((string) ($payload['hostname'] ?? $payload['host'] ?? $defaults['hostname']));
        $hostport = trim((string) ($payload['hostport'] ?? $payload['port'] ?? $defaults['hostport']));
        $database = trim((string) ($payload['database'] ?? $defaults['database']));
        $username = trim((string) ($payload['username'] ?? $defaults['username']));
        $password = (string) ($payload['password'] ?? $defaults['password']);
        $charset = trim((string) ($payload['charset'] ?? $defaults['charset'])) ?: 'utf8mb4';
        $prefix = (string) config('install.default_table_prefix', 'light_');

        if (strtolower($charset) === 'utf8') {
            $charset = 'utf8mb4';
        }

        if ($hostname === '' || $hostport === '' || $database === '' || $charset === '' || $username === '' || trim($password) === '') {
            throw new FailedException('数据库主机、端口、库名、字符集、用户名、密码均不能为空');
        }

        return [
            'type' => 'mysql',
            'hostname' => $hostname,
            'hostport' => $hostport,
            'database' => $database,
            'username' => $username,
            'password' => $password,
            'charset' => $charset,
            'prefix' => $prefix,
        ];
    }

    private function createServerPdo(array $config): PDO
    {
        try {
            return new PDO(
                sprintf(
                    'mysql:host=%s;port=%s;charset=%s',
                    $config['hostname'],
                    $config['hostport'],
                    $config['charset']
                ),
                $config['username'],
                $config['password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );
        } catch (Throwable $exception) {
            throw new FailedException('数据库连接失败: ' . $exception->getMessage());
        }
    }

    private function createDatabasePdo(array $config): PDO
    {
        try {
            return new PDO(
                sprintf(
                    'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                    $config['hostname'],
                    $config['hostport'],
                    $config['database'],
                    $config['charset']
                ),
                $config['username'],
                $config['password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );
        } catch (Throwable $exception) {
            throw new FailedException('数据库连接失败: ' . $exception->getMessage());
        }
    }

    private function databaseExists(PDO $pdo, string $database): bool
    {
        $statement = $pdo->prepare('SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?');
        $statement->execute([$database]);

        return (bool) $statement->fetchColumn();
    }

    private function isDatabaseEmpty(PDO $pdo, string $database): bool
    {
        $statement = $pdo->prepare(
            'SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_TYPE = ?'
        );
        $statement->execute([$database, 'BASE TABLE']);

        return (int) $statement->fetchColumn() === 0;
    }

    private function createDatabase(array $config): void
    {
        $pdo = $this->createServerPdo($config);

        $sql = sprintf(
            'CREATE DATABASE `%s` CHARACTER SET %s COLLATE utf8mb4_general_ci',
            str_replace('`', '``', $config['database']),
            $config['charset']
        );

        try {
            $pdo->exec($sql);
        } catch (Throwable $exception) {
            throw new FailedException('创建数据库失败: ' . $exception->getMessage());
        }
    }

    private function writeEnvironmentFile(array $config): void
    {
        $envFile = app()->getRootPath() . '.env';
        $parsed = is_file($envFile) ? parse_ini_file($envFile, true, INI_SCANNER_RAW) : [];
        if ($parsed === false) {
            $parsed = [];
        }

        $rootValues = [];
        $sections = [];

        foreach ($parsed as $key => $value) {
            if (is_array($value)) {
                $sections[$key] = $value;
            } else {
                $rootValues[$key] = $value;
            }
        }

        $sections['DATABASE'] = array_merge($sections['DATABASE'] ?? [], [
            'HOSTNAME' => $config['hostname'],
            'DATABASE' => $config['database'],
            'USERNAME' => $config['username'],
            'PASSWORD' => $config['password'],
            'HOSTPORT' => $config['hostport'],
            'CHARSET' => $config['charset'],
            'PREFIX' => $config['prefix'],
        ]);

        $content = '';
        foreach ($rootValues as $key => $value) {
            $content .= $this->formatEnvLine((string) $key, $value);
        }

        if ($rootValues !== []) {
            $content .= PHP_EOL;
        }

        foreach ($sections as $section => $values) {
            $content .= sprintf("[%s]%s", $section, PHP_EOL);
            foreach ($values as $key => $value) {
                $content .= $this->formatEnvLine((string) $key, $value);
            }
            $content .= PHP_EOL;
        }

        if (@file_put_contents($envFile, rtrim($content) . PHP_EOL) === false) {
            throw new FailedException('写入 .env 失败，请检查文件权限');
        }
    }

    private function formatEnvLine(string $key, mixed $value): string
    {
        if (is_bool($value)) {
            $value = $value ? 'true' : 'false';
        }

        return sprintf("%s = %s%s", strtoupper($key), (string) $value, PHP_EOL);
    }

    private function executeSqlFile(PDO $pdo, string $prefix): void
    {
        $sqlFile = (string) config('install.sql_file');
        if (!is_file($sqlFile)) {
            throw new FailedException('安装 SQL 文件不存在');
        }

        $sql = (string) file_get_contents($sqlFile);
        $defaultPrefix = (string) config('install.default_table_prefix', 'light_');

        if ($prefix !== $defaultPrefix) {
            $sql = str_replace($defaultPrefix, $prefix, $sql);
        }

        foreach ($this->splitSqlStatements($sql) as $statement) {
            $pdo->exec($statement);
        }
    }

    private function splitSqlStatements(string $sql): array
    {
        $sql = str_replace(["\r\n", "\r"], "\n", $sql);
        $statements = [];
        $buffer = '';
        $inBlockComment = false;

        foreach (explode("\n", $sql) as $line) {
            $trimmed = trim($line);

            if ($trimmed === '') {
                continue;
            }

            if ($inBlockComment) {
                if (str_contains($trimmed, '*/')) {
                    $inBlockComment = false;
                }
                continue;
            }

            if (str_starts_with($trimmed, '/*')) {
                if (!str_contains($trimmed, '*/')) {
                    $inBlockComment = true;
                }
                continue;
            }

            if (str_starts_with($trimmed, '--')) {
                continue;
            }

            $buffer .= $line . "\n";

            if (str_ends_with(rtrim($line), ';')) {
                $statement = trim($buffer);
                $statement = rtrim($statement, " \t\n\r;");
                if ($statement !== '') {
                    $statements[] = $statement;
                }
                $buffer = '';
            }
        }

        $tail = trim($buffer);
        if ($tail !== '') {
            $statements[] = $tail;
        }

        return $statements;
    }

    private function verifyInstallation(PDO $pdo, string $prefix): void
    {
        // Role and department tables were removed; installation is valid with these core tables.
        $requiredTables = [
            $prefix . 'user',
            $prefix . 'menu',
            $prefix . 'system_setting',
        ];

        foreach ($requiredTables as $table) {
            $statement = $pdo->query(sprintf('SELECT 1 FROM `%s` LIMIT 1', str_replace('`', '``', $table)));
            if ($statement === false) {
                throw new FailedException('安装校验失败，缺少核心表: ' . $table);
            }
        }
    }

    private function rollbackFailedInstall(array $config, bool $databaseCreated, bool $databaseExisted): void
    {
        try {
            if ($databaseCreated) {
                $this->dropDatabase($config);
                return;
            }

            if ($databaseExisted) {
                $this->dropAllTables($config);
            }
        } catch (Throwable $exception) {
            $this->stateStore->appendCleanupLog('安装失败后的数据库清理未完成: ' . $exception->getMessage());
        }
    }

    private function dropDatabase(array $config): void
    {
        $serverPdo = $this->createServerPdo($config);
        $serverPdo->exec(sprintf('DROP DATABASE IF EXISTS `%s`', str_replace('`', '``', $config['database'])));
    }

    private function dropAllTables(array $config): void
    {
        $pdo = $this->createDatabasePdo($config);
        $statement = $pdo->prepare(
            'SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_TYPE = ?'
        );
        $statement->execute([$config['database'], 'BASE TABLE']);

        $tables = $statement->fetchAll(PDO::FETCH_COLUMN) ?: [];
        if ($tables === []) {
            return;
        }

        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        foreach ($tables as $table) {
            $pdo->exec(sprintf('DROP TABLE IF EXISTS `%s`', str_replace('`', '``', (string) $table)));
        }
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
    }

    private function normalizeException(Throwable $exception): FailedException
    {
        if ($exception instanceof FailedException) {
            return $exception;
        }

        return new FailedException('安装失败: ' . $exception->getMessage());
    }

    private function getLinks(): array
    {
        return (array) config('install.links', [
            'login' => '/#/auth/login',
        ]);
    }
}
