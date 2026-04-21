<?php

namespace app\service\system;

use app\model\system\Dict;
use core\base\BaseService;
use core\exception\FailedException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use think\cache\driver\File as FileCacheDriver;
use think\facade\Cache;

class CacheService extends BaseService
{
    private const CACHE_FILE_SUFFIX = '.php';
    private const CACHE_FILE_HEADER_LENGTH = 32;
    private const LOGIN_ATTEMPT_KEYS = ['count', 'last_attempt_time'];

    public function overview(): array
    {
        $this->ensureSuperAdmin();

        $dictTypes = $this->getDictTypes();
        $runtime = [
            'supported' => false,
            'driver' => Cache::getDefaultDriver() ?: 'file',
            'path' => '',
            'file_count' => 0,
            'size_bytes' => 0,
            'protected_file_count' => 0
        ];

        $fileCacheStore = $this->getFileCacheStore(false);
        if ($fileCacheStore) {
            $cacheDir = $this->getCacheDirectory();
            $runtimeFiles = $this->collectCacheFiles($cacheDir);
            $stats = $this->calculateFileStats($runtimeFiles);
            $protectedFiles = $this->getProtectedRuntimeFiles($fileCacheStore, $runtimeFiles);

            $runtime = [
                'supported' => true,
                'driver' => Cache::getDefaultDriver() ?: 'file',
                'path' => $cacheDir,
                'file_count' => $stats['file_count'],
                'size_bytes' => $stats['size_bytes'],
                'protected_file_count' => count($protectedFiles)
            ];
        }

        return [
            'browser' => [
                'scope' => 'current_browser'
            ],
            'dict' => [
                'type_count' => count($dictTypes),
                'cached_count' => $this->countCachedDictTypes($dictTypes)
            ],
            'runtime' => $runtime
        ];
    }

    public function refreshDictCache(): array
    {
        $this->ensureSuperAdmin();

        $dictTypes = $this->getDictTypes();
        foreach ($dictTypes as $type) {
            Cache::delete('dict_' . $type);
            Cache::delete('dict_' . $type . '_lock');
        }

        /** @var DictService $dictService */
        $dictService = $this->app->make(DictService::class);
        $dictService->updateCache();

        return [
            'type_count' => count($dictTypes),
            'cached_count' => $this->countCachedDictTypes($dictTypes),
            'refreshed_count' => count($dictTypes)
        ];
    }

    public function clearRuntimeCache(): array
    {
        $this->ensureSuperAdmin();

        $fileCacheStore = $this->getFileCacheStore();
        $cacheDir = $this->getCacheDirectory();
        $runtimeFiles = $this->collectCacheFiles($cacheDir);
        $protectedFiles = $this->getProtectedRuntimeFiles($fileCacheStore, $runtimeFiles);
        $protectedMap = array_fill_keys($protectedFiles, true);

        $removedCount = 0;
        $removedSizeBytes = 0;
        $failedCount = 0;
        $skippedCount = 0;

        foreach ($runtimeFiles as $filePath) {
            $normalizedPath = $this->normalizePath($filePath);
            if (isset($protectedMap[$normalizedPath])) {
                $skippedCount++;
                continue;
            }

            $fileSize = is_file($filePath) ? (int) filesize($filePath) : 0;
            if (@unlink($filePath)) {
                $removedCount++;
                $removedSizeBytes += $fileSize;
                continue;
            }

            $failedCount++;
        }

        $this->removeEmptyDirectories($cacheDir);
        clearstatcache();

        $remainingFiles = $this->collectCacheFiles($cacheDir);
        $remainingStats = $this->calculateFileStats($remainingFiles);

        return [
            'removed_count' => $removedCount,
            'removed_size_bytes' => $removedSizeBytes,
            'skipped_count' => $skippedCount,
            'failed_count' => $failedCount,
            'remaining_file_count' => $remainingStats['file_count'],
            'remaining_size_bytes' => $remainingStats['size_bytes'],
            'protected_file_count' => count($protectedFiles)
        ];
    }

    private function ensureSuperAdmin(): void
    {
        if (!is_super_admin()) {
            throw new FailedException('仅超级管理员可操作', httpCode: 403);
        }
    }

    private function getDictTypes(): array
    {
        return array_values(array_unique(array_map(
            'strval',
            Dict::distinct(true)->column('type')
        )));
    }

    private function countCachedDictTypes(array $dictTypes): int
    {
        $count = 0;
        foreach ($dictTypes as $type) {
            if (Cache::has('dict_' . $type)) {
                $count++;
            }
        }

        return $count;
    }

    private function getFileCacheStore(bool $throwWhenUnsupported = true): ?FileCacheDriver
    {
        $defaultDriver = Cache::getDefaultDriver() ?: 'file';
        $storeType = strtolower((string) Cache::getStoreConfig($defaultDriver, 'type', 'file'));

        if ($storeType !== 'file') {
            if ($throwWhenUnsupported) {
                throw new FailedException('当前缓存驱动不是文件缓存，暂不支持运行缓存清理');
            }

            return null;
        }

        $store = Cache::store($defaultDriver);
        if ($store instanceof FileCacheDriver) {
            return $store;
        }

        if ($throwWhenUnsupported) {
            throw new FailedException('当前缓存驱动不是文件缓存，暂不支持运行缓存清理');
        }

        return null;
    }

    private function getCacheDirectory(): string
    {
        $defaultDriver = Cache::getDefaultDriver() ?: 'file';
        $path = (string) Cache::getStoreConfig($defaultDriver, 'path', '');

        if ($path === '') {
            $path = $this->app->getRuntimePath() . 'cache';
        }

        return rtrim($path, DIRECTORY_SEPARATOR);
    }

    private function collectCacheFiles(string $cacheDir): array
    {
        if (!is_dir($cacheDir)) {
            return [];
        }

        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($cacheDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $item) {
            if (!$item->isFile()) {
                continue;
            }

            $pathname = $item->getPathname();
            if (!str_ends_with($pathname, self::CACHE_FILE_SUFFIX)) {
                continue;
            }

            $files[] = $pathname;
        }

        return $files;
    }

    private function calculateFileStats(array $files): array
    {
        $sizeBytes = 0;
        foreach ($files as $filePath) {
            if (!is_file($filePath)) {
                continue;
            }

            $sizeBytes += (int) filesize($filePath);
        }

        return [
            'file_count' => count($files),
            'size_bytes' => $sizeBytes
        ];
    }

    private function getProtectedRuntimeFiles(FileCacheDriver $fileCacheStore, array $runtimeFiles): array
    {
        $protectedFiles = $this->getJwtBlacklistFiles($fileCacheStore);

        foreach ($runtimeFiles as $filePath) {
            if ($this->isLoginAttemptCacheFile($filePath)) {
                $protectedFiles[] = $filePath;
            }
        }

        $normalizedFiles = [];
        foreach ($protectedFiles as $filePath) {
            if (!is_string($filePath) || $filePath === '') {
                continue;
            }

            $normalized = $this->normalizePath($filePath);
            if ($normalized === '') {
                continue;
            }

            $normalizedFiles[$normalized] = true;
        }

        return array_keys($normalizedFiles);
    }

    private function getJwtBlacklistFiles(FileCacheDriver $fileCacheStore): array
    {
        $jwtCachePrefix = (string) config('jwt.default.cache_prefix', 'speed_jwt');
        $tagItems = $fileCacheStore->getTagItems($jwtCachePrefix);
        $tagFile = $fileCacheStore->getCacheKey($fileCacheStore->getTagKey($jwtCachePrefix));

        $files = array_values(array_filter(is_array($tagItems) ? $tagItems : [], 'is_string'));
        $files[] = $tagFile;

        return $files;
    }

    private function isLoginAttemptCacheFile(string $filePath): bool
    {
        $payload = $this->readCachePayload($filePath);
        if (!is_array($payload)) {
            return false;
        }

        foreach (self::LOGIN_ATTEMPT_KEYS as $key) {
            if (!array_key_exists($key, $payload) || !is_numeric($payload[$key])) {
                return false;
            }
        }

        $lastAttemptTime = (int) $payload['last_attempt_time'];
        $attemptCount = (int) $payload['count'];

        return $attemptCount >= 0
            && $attemptCount <= 100
            && $lastAttemptTime > 0
            && $lastAttemptTime <= time() + 60
            && $lastAttemptTime >= time() - 86400;
    }

    private function readCachePayload(string $filePath): mixed
    {
        $content = @file_get_contents($filePath);
        if ($content === false || strlen($content) <= self::CACHE_FILE_HEADER_LENGTH) {
            return null;
        }

        $payload = substr($content, self::CACHE_FILE_HEADER_LENGTH);

        if ($this->isCacheCompressed() && function_exists('gzuncompress')) {
            $uncompressed = @gzuncompress($payload);
            if ($uncompressed !== false) {
                $payload = $uncompressed;
            }
        }

        if (is_numeric($payload)) {
            return $payload + 0;
        }

        $value = @unserialize($payload);
        return $value === false && $payload !== serialize(false) ? null : $value;
    }

    private function isCacheCompressed(): bool
    {
        $defaultDriver = Cache::getDefaultDriver() ?: 'file';
        return (bool) Cache::getStoreConfig($defaultDriver, 'data_compress', false);
    }

    private function removeEmptyDirectories(string $cacheDir): void
    {
        if (!is_dir($cacheDir)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($cacheDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            if (!$item->isDir()) {
                continue;
            }

            $pathname = $item->getPathname();
            if ($pathname === $cacheDir) {
                continue;
            }

            $children = @scandir($pathname);
            if ($children !== false && count($children) <= 2) {
                @rmdir($pathname);
            }
        }
    }

    private function normalizePath(string $path): string
    {
        $normalized = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
        return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN'
            ? strtolower($normalized)
            : $normalized;
    }
}
