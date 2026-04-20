<?php

declare(strict_types=1);

namespace core\install;

use core\exception\FailedException;
use PDO;
use Throwable;

class InstallGuard
{
    public function __construct(private readonly InstallStateStore $stateStore)
    {
    }

    public function isInstalled(): bool
    {
        if ($this->stateStore->hasLock()) {
            return true;
        }

        return $this->hasInstalledCoreTables();
    }

    public function ensureNotInstalled(): void
    {
        if ($this->isInstalled()) {
            throw new FailedException('系统已安装，安装入口已关闭', httpCode: 404);
        }
    }

    public function ensureInstalled(): void
    {
        if (!$this->isInstalled()) {
            throw new FailedException('系统尚未安装，无法执行该操作', httpCode: 400);
        }
    }

    private function hasInstalledCoreTables(): bool
    {
        $config = (array) config('database.connections.mysql', []);

        if (
            empty($config['hostname'])
            || empty($config['database'])
            || empty($config['username'])
        ) {
            return false;
        }

        try {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                $config['hostname'],
                $config['hostport'] ?? '3306',
                $config['database'],
                $config['charset'] ?? 'utf8mb4'
            );

            $pdo = new PDO($dsn, (string) $config['username'], (string) ($config['password'] ?? ''), [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);

            $prefix = (string) ($config['prefix'] ?? config('install.default_table_prefix', 'light_'));
            $tables = [
                $prefix . 'user',
                $prefix . 'role',
                $prefix . 'system_setting',
            ];

            $placeholders = implode(',', array_fill(0, count($tables), '?'));
            $sql = "SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME IN ({$placeholders})";

            $statement = $pdo->prepare($sql);
            $statement->execute(array_merge([(string) $config['database']], $tables));

            return (int) $statement->fetchColumn() >= 2;
        } catch (Throwable) {
            return false;
        }
    }
}
