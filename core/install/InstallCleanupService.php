<?php

declare(strict_types=1);

namespace core\install;

use core\exception\FailedException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Throwable;

class InstallCleanupService
{
    public function __construct(private readonly InstallStateStore $stateStore)
    {
    }

    public function cleanup(string $token = '', bool $force = false): array
    {
        if (!$this->stateStore->hasLock()) {
            throw new FailedException('安装尚未完成，当前无需清理安装目录', httpCode: 400);
        }

        if (!$force && !$this->stateStore->verifyCleanupToken($token)) {
            throw new FailedException('清理令牌无效，请回到安装完成页重试', httpCode: 403);
        }

        $installDir = rtrim((string) config('install.install_dir'), '\\/');
        $this->stateStore->mergeState([
            'status' => 'cleanup_running',
            'cleanup_status' => 'running',
            'cleanup_required' => true,
        ]);

        try {
            if (is_dir($installDir)) {
                $this->deleteDirectory($installDir);
            }

            $this->stateStore->clearCleanupToken();
            $doneAt = date('Y-m-d H:i:s');
            $this->stateStore->mergeState([
                'status' => 'cleanup_success',
                'cleanup_status' => 'success',
                'cleanup_required' => false,
                'cleanup_done_at' => $doneAt,
            ]);

            return [
                'deleted' => true,
                'cleanup_done_at' => $doneAt,
                'path' => $installDir,
            ];
        } catch (Throwable $exception) {
            $message = '安装目录自动清理失败: ' . $exception->getMessage();
            $this->stateStore->appendCleanupLog($message);
            $this->stateStore->mergeState([
                'status' => 'cleanup_failed',
                'cleanup_status' => 'failed',
                'cleanup_required' => true,
            ]);

            throw new FailedException($message);
        }
    }

    private function deleteDirectory(string $directory): void
    {
        $rootPath = rtrim(app()->getRootPath(), '\\/');
        $normalizedDirectory = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $directory);
        $normalizedRoot = str_replace(
            ['/', '\\'],
            DIRECTORY_SEPARATOR,
            $rootPath . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'install'
        );

        if ($normalizedDirectory !== $normalizedRoot) {
            throw new FailedException('安装目录路径校验失败，已中止删除操作');
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            $pathname = $item->getPathname();

            if ($item->isDir()) {
                if (!@rmdir($pathname) && is_dir($pathname)) {
                    throw new FailedException('删除目录失败: ' . $pathname);
                }
                continue;
            }

            if (!@unlink($pathname) && file_exists($pathname)) {
                throw new FailedException('删除文件失败: ' . $pathname);
            }
        }

        if (!@rmdir($directory) && is_dir($directory)) {
            throw new FailedException('删除安装目录失败: ' . $directory);
        }
    }
}
