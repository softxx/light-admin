<?php

namespace app\service\system;

use app\model\system\Dict;
use core\base\BaseService;
use core\exception\FailedException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use think\cache\driver\File as FileCacheDriver;
use think\facade\Cache;

/**
 * 后台缓存管理服务。
 *
 * 这里统一处理三类缓存：
 * 1. 浏览器缓存：前端自行清理，本服务只返回展示用的作用域信息。
 * 2. 字典缓存：按字典类型清理并重建。
 * 3. 运行缓存：仅清理普通文件缓存，并保留登录安全相关缓存。
 */
class CacheService extends BaseService
{
    // ThinkPHP 文件缓存默认以 PHP 文件形式保存，运行缓存清理只处理这类缓存文件。
    private const CACHE_FILE_SUFFIX = '.php';

    // 文件缓存前 32 字节为过期时间等头信息，真实缓存值从该偏移量后开始。
    private const CACHE_FILE_HEADER_LENGTH = 32;

    // 登录失败限制缓存的特征字段，命中后会被保留，避免清缓存绕过登录风控。
    private const LOGIN_ATTEMPT_KEYS = ['count', 'last_attempt_time'];

    /**
     * 获取缓存概览，供前端展示浏览器、字典和运行缓存状态。
     */
    public function overview(): array
    {
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

            // 安全相关缓存不会被运行缓存清理删除，这里提前统计给前端展示。
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

    /**
     * 刷新字典缓存。
     *
     * 先删除每个字典类型对应的缓存和锁，再调用字典服务统一重建缓存。
     */
    public function refreshDictCache(): array
    {
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

    /**
     * 清理运行缓存。
     *
     * 只删除普通文件缓存；JWT 黑名单和登录失败限制缓存会被识别并跳过。
     */
    public function clearRuntimeCache(): array
    {
        $fileCacheStore = $this->getFileCacheStore();
        $cacheDir = $this->getCacheDirectory();
        $runtimeFiles = $this->collectCacheFiles($cacheDir);
        $protectedFiles = $this->getProtectedRuntimeFiles($fileCacheStore, $runtimeFiles);

        // 使用规范化路径作为 key，兼容 Windows 路径大小写和分隔符差异。
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

    private function getDictTypes(): array
    {
        // 按字典类型去重，后续缓存 key 统一使用 dict_{type}。
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
        // 运行缓存清理依赖文件缓存目录；非 file 驱动时只允许展示“不支持”状态。
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
        // 优先读取当前缓存驱动配置，未配置时回退到 runtime/cache。
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

        // 只收集叶子文件，避免误处理目录；再按文件缓存后缀过滤。
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
        // JWT 黑名单用于令牌失效控制，必须在清理运行缓存时保留。
        $protectedFiles = $this->getJwtBlacklistFiles($fileCacheStore);

        // 登录失败限制缓存没有固定 tag，只能读取 payload 后按字段特征识别。
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
        // JWT 黑名单使用 ThinkPHP tag 缓存，既要保留 tag 文件，也要保留 tag 下的缓存项。
        $jwtCachePrefix = (string) config('jwt.default.cache_prefix', 'light_jwt');
        $tagItems = $fileCacheStore->getTagItems($jwtCachePrefix);
        $tagFile = $fileCacheStore->getCacheKey($fileCacheStore->getTagKey($jwtCachePrefix));

        $files = array_values(array_filter(is_array($tagItems) ? $tagItems : [], 'is_string'));
        $files[] = $tagFile;

        return $files;
    }

    private function isLoginAttemptCacheFile(string $filePath): bool
    {
        // 通过缓存内容判断是否为登录失败计数，避免依赖不稳定的文件名。
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

        // 增加合理范围判断，降低普通业务缓存被误判为登录限制缓存的概率。
        return $attemptCount >= 0
            && $attemptCount <= 100
            && $lastAttemptTime > 0
            && $lastAttemptTime <= time() + 60
            && $lastAttemptTime >= time() - 86400;
    }

    private function readCachePayload(string $filePath): mixed
    {
        // ThinkPHP 文件缓存内容由固定长度头部 + 序列化 payload 组成。
        $content = @file_get_contents($filePath);
        if ($content === false || strlen($content) <= self::CACHE_FILE_HEADER_LENGTH) {
            return null;
        }

        $payload = substr($content, self::CACHE_FILE_HEADER_LENGTH);

        if ($this->isCacheCompressed() && function_exists('gzuncompress')) {
            // 开启 data_compress 时 payload 会被压缩，读取前需要先尝试解压。
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

        // 子目录中的缓存文件删除后，按从深到浅的顺序清理空目录。
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
        // Windows 文件系统路径大小写不敏感，统一小写后再做集合匹配。
        $normalized = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
        return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN'
            ? strtolower($normalized)
            : $normalized;
    }
}
