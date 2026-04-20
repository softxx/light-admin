<?php

declare(strict_types=1);

namespace core\install;

use core\exception\FailedException;

class InstallStateStore
{
    private string $metaPath;

    public function __construct()
    {
        $this->metaPath = rtrim((string) config('install.meta_path'), '\\/') . DIRECTORY_SEPARATOR;
    }

    public function getMetaPath(): string
    {
        return $this->metaPath;
    }

    public function getLockFile(): string
    {
        return $this->metaPath . 'install.lock';
    }

    public function getStateFile(): string
    {
        return $this->metaPath . 'state.json';
    }

    public function getMutexFile(): string
    {
        return $this->metaPath . 'mutex.lock';
    }

    public function getCleanupTokenFile(): string
    {
        return $this->metaPath . 'cleanup.token';
    }

    public function getCleanupLogFile(): string
    {
        return $this->metaPath . 'cleanup.log';
    }

    public function ensureMetaDirectory(): void
    {
        if (is_dir($this->metaPath)) {
            return;
        }

        if (!@mkdir($concurrentDirectory = $this->metaPath, 0755, true) && !is_dir($concurrentDirectory)) {
            throw new FailedException('无法创建安装状态目录: ' . $this->metaPath);
        }
    }

    public function hasLock(): bool
    {
        return is_file($this->getLockFile());
    }

    public function writeLock(array $payload = []): void
    {
        $this->ensureMetaDirectory();
        $content = json_encode(
            array_merge([
                'installed_at' => date('Y-m-d H:i:s'),
            ], $payload),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
        );

        if ($content === false || @file_put_contents($this->getLockFile(), $content . PHP_EOL) === false) {
            throw new FailedException('写入安装锁失败');
        }
    }

    public function getState(): array
    {
        $defaultState = [
            'status' => 'idle',
            'cleanup_status' => 'pending',
            'cleanup_required' => false,
            'installed_at' => null,
            'cleanup_done_at' => null,
            'app_version' => null,
            'db_host' => null,
            'db_name' => null,
            'table_prefix' => (string) config('install.default_table_prefix', 'light_'),
            'links' => config('install.links', []),
        ];

        $stateFile = $this->getStateFile();
        if (!is_file($stateFile)) {
            return $defaultState;
        }

        $content = file_get_contents($stateFile);
        if ($content === false || trim($content) === '') {
            return $defaultState;
        }

        $decoded = json_decode($content, true);
        if (!is_array($decoded)) {
            return $defaultState;
        }

        return array_merge($defaultState, $decoded);
    }

    public function writeState(array $state): array
    {
        $this->ensureMetaDirectory();

        $content = json_encode($state, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        if ($content === false || @file_put_contents($this->getStateFile(), $content . PHP_EOL) === false) {
            throw new FailedException('写入安装状态失败');
        }

        return $state;
    }

    public function mergeState(array $patch): array
    {
        return $this->writeState(array_merge($this->getState(), $patch));
    }

    public function withMutex(callable $callback)
    {
        $this->ensureMetaDirectory();
        $handle = fopen($this->getMutexFile(), 'c+');

        if ($handle === false) {
            throw new FailedException('无法创建安装锁文件');
        }

        try {
            if (!flock($handle, LOCK_EX | LOCK_NB)) {
                throw new FailedException('已有安装任务正在执行，请稍后再试');
            }

            return $callback();
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    public function issueCleanupToken(): string
    {
        $this->ensureMetaDirectory();
        $token = bin2hex(random_bytes(32));
        $hash = hash('sha256', $token);

        if (@file_put_contents($this->getCleanupTokenFile(), $hash . PHP_EOL) === false) {
            throw new FailedException('生成安装清理令牌失败');
        }

        return $token;
    }

    public function verifyCleanupToken(string $token): bool
    {
        if ($token === '' || !is_file($this->getCleanupTokenFile())) {
            return false;
        }

        $storedHash = trim((string) file_get_contents($this->getCleanupTokenFile()));
        if ($storedHash === '') {
            return false;
        }

        return hash_equals($storedHash, hash('sha256', $token));
    }

    public function clearCleanupToken(): void
    {
        $tokenFile = $this->getCleanupTokenFile();
        if (is_file($tokenFile)) {
            @unlink($tokenFile);
        }
    }

    public function appendCleanupLog(string $message): void
    {
        $this->ensureMetaDirectory();
        $line = sprintf("[%s] %s%s", date('Y-m-d H:i:s'), $message, PHP_EOL);
        @file_put_contents($this->getCleanupLogFile(), $line, FILE_APPEND);
    }
}
