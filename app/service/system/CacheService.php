<?php

namespace app\service\system;

use app\model\system\Dict;
use core\base\BaseService;
use core\exception\FailedException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use think\cache\driver\File as FileCacheDriver;
use think\cache\driver\Redis as RedisCacheDriver;
use think\facade\Cache;
use think\facade\Config;
use Throwable;

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

    private const SUPPORTED_DRIVERS = ['file', 'redis'];

    private const CACHE_ENV_SECTION = 'CACHE';

    private const CACHE_ENV_DEFAULTS = [
        'DRIVER' => 'file',
        'REDIS_HOST' => '127.0.0.1',
        'REDIS_PORT' => 6379,
        'REDIS_PASSWORD' => '',
        'REDIS_SELECT' => 0,
        'REDIS_TIMEOUT' => 3,
        'REDIS_PERSISTENT' => false,
        'REDIS_PREFIX' => 'light_cache:',
        'REDIS_EXPIRE' => 0,
    ];

    /**
     * 获取缓存概览，供前端展示浏览器、字典和运行缓存状态。
     */
    public function overview(): array
    {
        $dictTypes = $this->getDictTypes();
        $runtime = [
            'supported' => false,
            'driver' => $this->getCurrentDriverName(),
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
                'driver' => $this->getCurrentDriverName(),
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
            'runtime' => $runtime,
            'setting' => $this->getSetting()
        ];
    }

    /**
     * 获取当前缓存驱动配置。
     *
     * 密码只返回是否已配置，避免管理员页面反显敏感值；保存时留空会沿用旧密码。
     */
    public function getSetting(): array
    {
        $redis = $this->getRedisConfig();

        return [
            'driver' => $this->getCurrentDriverName(),
            'drivers' => self::SUPPORTED_DRIVERS,
            'redis' => [
                'host' => $redis['host'],
                'port' => $redis['port'],
                'password' => '',
                'password_set' => $redis['password'] !== '',
                'clear_password' => false,
                'select' => $redis['select'],
                'timeout' => $redis['timeout'],
                'persistent' => $redis['persistent'],
                'prefix' => $redis['prefix'],
                'expire' => $redis['expire'],
            ],
            'health' => $this->checkCurrentCacheHealth(),
        ];
    }

    /**
     * 保存缓存驱动配置。
     *
     * 切换到 Redis 前会先写入测试 key，防止错误参数把全局缓存切到不可用状态。
     *
     * @param array{driver:string,redis?:array} $data
     * @return array
     */
    public function saveSetting(array $data): array
    {
        $driver = $this->normalizeDriver($data['driver'] ?? self::CACHE_ENV_DEFAULTS['DRIVER']);
        $redis = $this->normalizeRedisConfig((array) ($data['redis'] ?? []), $this->getRedisConfig());

        if ($driver === 'redis') {
            $this->assertRedisAvailable($redis);
        }

        $this->persistCacheEnv($driver, $redis);
        $this->clearConfigCacheFiles();
        $this->applyRuntimeCacheConfig($driver, $redis);

        return $this->getSetting();
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

        try {
            foreach ($dictTypes as $type) {
                if (Cache::has('dict_' . $type)) {
                    $count++;
                }
            }
        } catch (Throwable) {
            // 缓存驱动异常时仍允许进入缓存管理页修复配置。
            return 0;
        }

        return $count;
    }

    private function getFileCacheStore(bool $throwWhenUnsupported = true): ?FileCacheDriver
    {
        // 运行缓存清理依赖文件缓存目录；非 file 驱动时只允许展示“不支持”状态。
        $defaultDriver = $this->getCurrentDriverName();
        try {
            $storeType = strtolower((string) Cache::getStoreConfig($defaultDriver, 'type', 'file'));
        } catch (Throwable) {
            if ($throwWhenUnsupported) {
                throw new FailedException('当前缓存驱动配置不存在，暂不支持运行缓存清理');
            }

            return null;
        }

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
        $defaultDriver = $this->getCurrentDriverName();
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

    private function readCachePayload(string $filePath): ?array
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

        return $this->parseLoginAttemptPayload($payload);
    }

    private function parseLoginAttemptPayload(string $payload): ?array
    {
        $decoded = json_decode($payload, true);
        if (is_array($decoded)) {
            return $this->onlyLoginAttemptFields($decoded);
        }

        // 文件缓存默认使用 PHP 序列化数组，这里只解析需要识别登录限制的数值字段。
        $count = $this->parseSerializedNumericField($payload, 'count');
        $lastAttemptTime = $this->parseSerializedNumericField($payload, 'last_attempt_time');
        if ($count === null || $lastAttemptTime === null) {
            return null;
        }

        return [
            'count' => $count,
            'last_attempt_time' => $lastAttemptTime,
        ];
    }

    private function onlyLoginAttemptFields(array $payload): ?array
    {
        $result = [];
        foreach (self::LOGIN_ATTEMPT_KEYS as $key) {
            if (!isset($payload[$key]) || !is_numeric($payload[$key])) {
                return null;
            }

            $result[$key] = $payload[$key] + 0;
        }

        return $result;
    }

    private function parseSerializedNumericField(string $payload, string $key): int|float|null
    {
        $field = preg_quote($key, '/');
        $length = strlen($key);
        $pattern = '/s:' . $length . ':"' . $field . '";(?:i:(-?\d+);|d:(-?\d+(?:\.\d+)?);|s:\d+:"(-?\d+(?:\.\d+)?)";)/';
        if (!preg_match($pattern, $payload, $matches)) {
            return null;
        }

        $value = array_values(array_filter(array_slice($matches, 1), static fn (string $item) => $item !== ''))[0] ?? null;
        if ($value === null || !is_numeric($value)) {
            return null;
        }

        return str_contains($value, '.') ? (float) $value : (int) $value;
    }

    private function isCacheCompressed(): bool
    {
        $defaultDriver = $this->getCurrentDriverName();
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

    private function getCurrentDriverName(): string
    {
        $driver = strtolower(trim((string) (Cache::getDefaultDriver() ?: self::CACHE_ENV_DEFAULTS['DRIVER'])));
        return $driver !== '' ? $driver : self::CACHE_ENV_DEFAULTS['DRIVER'];
    }

    private function normalizeDriver(mixed $driver): string
    {
        $driver = strtolower(trim((string) $driver));
        if (!in_array($driver, self::SUPPORTED_DRIVERS, true)) {
            throw new FailedException('缓存驱动仅支持 file 或 redis');
        }

        return $driver;
    }

    private function getRedisConfig(): array
    {
        $config = (array) config('cache.stores.redis', []);

        return [
            'host' => trim((string) ($config['host'] ?? self::CACHE_ENV_DEFAULTS['REDIS_HOST'])) ?: self::CACHE_ENV_DEFAULTS['REDIS_HOST'],
            'port' => (int) ($config['port'] ?? self::CACHE_ENV_DEFAULTS['REDIS_PORT']) ?: self::CACHE_ENV_DEFAULTS['REDIS_PORT'],
            'password' => (string) ($config['password'] ?? self::CACHE_ENV_DEFAULTS['REDIS_PASSWORD']),
            'select' => max(0, (int) ($config['select'] ?? self::CACHE_ENV_DEFAULTS['REDIS_SELECT'])),
            'timeout' => max(0, (int) ($config['timeout'] ?? self::CACHE_ENV_DEFAULTS['REDIS_TIMEOUT'])),
            'persistent' => $this->toBool($config['persistent'] ?? self::CACHE_ENV_DEFAULTS['REDIS_PERSISTENT']),
            'prefix' => (string) ($config['prefix'] ?? self::CACHE_ENV_DEFAULTS['REDIS_PREFIX']),
            'expire' => max(0, (int) ($config['expire'] ?? self::CACHE_ENV_DEFAULTS['REDIS_EXPIRE'])),
        ];
    }

    private function normalizeRedisConfig(array $payload, array $current): array
    {
        $redis = $current;

        if (array_key_exists('host', $payload)) {
            $redis['host'] = trim((string) $payload['host']);
        }

        if ($redis['host'] === '') {
            $redis['host'] = self::CACHE_ENV_DEFAULTS['REDIS_HOST'];
        }

        $redis['port'] = $this->normalizeInteger($payload['port'] ?? $redis['port'], 1, 65535, 'Redis 端口');
        $redis['select'] = $this->normalizeInteger($payload['select'] ?? $redis['select'], 0, 255, 'Redis 数据库');
        $redis['timeout'] = $this->normalizeInteger($payload['timeout'] ?? $redis['timeout'], 0, 60, 'Redis 连接超时');
        $redis['expire'] = $this->normalizeInteger($payload['expire'] ?? $redis['expire'], 0, 315360000, '缓存有效期');
        $redis['persistent'] = $this->toBool($payload['persistent'] ?? $redis['persistent']);

        if (array_key_exists('prefix', $payload)) {
            $redis['prefix'] = trim((string) $payload['prefix']);
        }

        $clearPassword = $this->toBool($payload['clear_password'] ?? false);
        if ($clearPassword) {
            $redis['password'] = '';
        } elseif (array_key_exists('password', $payload) && (string) $payload['password'] !== '') {
            $redis['password'] = (string) $payload['password'];
        }

        if (mb_strlen($redis['host']) > 255) {
            throw new FailedException('Redis 主机地址不能超过255个字符');
        }

        if (mb_strlen($redis['password']) > 255) {
            throw new FailedException('Redis 密码不能超过255个字符');
        }

        if (mb_strlen($redis['prefix']) > 100) {
            throw new FailedException('Redis Key 前缀不能超过100个字符');
        }

        return $redis;
    }

    private function normalizeInteger(mixed $value, int $min, int $max, string $label): int
    {
        if ($value === '') {
            $value = $min;
        }

        $number = filter_var($value, FILTER_VALIDATE_INT);
        if ($number === false || $number < $min || $number > $max) {
            throw new FailedException($label . '必须是 ' . $min . ' 到 ' . $max . ' 之间的整数');
        }

        return (int) $number;
    }

    private function toBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value !== 0;
        }

        return in_array(strtolower((string) $value), ['1', 'true', 'on', 'yes'], true);
    }

    private function assertRedisAvailable(array $redis): void
    {
        if (!extension_loaded('redis') && !class_exists('\Predis\Client')) {
            throw new FailedException('当前 PHP 未安装 redis 扩展或 Predis，无法启用 Redis 缓存');
        }

        try {
            $store = new RedisCacheDriver($this->buildRedisStoreConfig($redis));
            $key = '__cache_switch_test:' . bin2hex(random_bytes(6));
            $store->set($key, 'ok', 5);
            $store->delete($key);
        } catch (Throwable $e) {
            throw new FailedException('Redis 连接失败：' . $e->getMessage());
        }
    }

    private function buildRedisStoreConfig(array $redis): array
    {
        return [
            'type' => 'Redis',
            'host' => $redis['host'],
            'port' => $redis['port'],
            'password' => $redis['password'],
            'select' => $redis['select'],
            'timeout' => $redis['timeout'],
            'persistent' => $redis['persistent'],
            'prefix' => $redis['prefix'],
            'expire' => $redis['expire'],
            'tag_prefix' => 'tag:',
            'serialize' => [],
        ];
    }

    private function persistCacheEnv(string $driver, array $redis): void
    {
        $envPath = $this->getEnvPath();
        $envDir = dirname($envPath);

        if ((is_file($envPath) && !is_writable($envPath)) || (!is_file($envPath) && !is_writable($envDir))) {
            throw new FailedException('.env 文件不可写，无法保存缓存配置');
        }

        $content = is_file($envPath) ? file_get_contents($envPath) : '';
        if ($content === false) {
            throw new FailedException('读取 .env 文件失败，无法保存缓存配置');
        }

        $values = [
            'DRIVER' => $driver,
            'REDIS_HOST' => $redis['host'],
            'REDIS_PORT' => $redis['port'],
            'REDIS_PASSWORD' => $redis['password'],
            'REDIS_SELECT' => $redis['select'],
            'REDIS_TIMEOUT' => $redis['timeout'],
            'REDIS_PERSISTENT' => $redis['persistent'],
            'REDIS_PREFIX' => $redis['prefix'],
            'REDIS_EXPIRE' => $redis['expire'],
        ];

        $newContent = $this->upsertEnvSection($content, self::CACHE_ENV_SECTION, $values);
        if (file_put_contents($envPath, $newContent, LOCK_EX) === false) {
            throw new FailedException('写入 .env 文件失败，无法保存缓存配置');
        }
    }

    private function getEnvPath(): string
    {
        return rtrim($this->app->getRootPath(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '.env';
    }

    private function upsertEnvSection(string $content, string $section, array $values): string
    {
        $eol = str_contains($content, "\r\n") ? "\r\n" : "\n";
        $lines = $content === '' ? [] : preg_split('/\r\n|\n|\r/', $content);
        $sectionStart = null;
        $sectionEnd = count($lines);

        foreach ($lines as $index => $line) {
            if (!preg_match('/^\s*\[([^\]]+)]\s*$/', (string) $line, $matches)) {
                continue;
            }

            if (strtoupper($matches[1]) === strtoupper($section)) {
                $sectionStart = $index;
                continue;
            }

            if ($sectionStart !== null && $index > $sectionStart) {
                $sectionEnd = $index;
                break;
            }
        }

        $formatted = [];
        foreach ($values as $key => $value) {
            $formatted[strtoupper($key)] = strtoupper($key) . ' = ' . $this->formatEnvValue($value);
        }

        if ($sectionStart === null) {
            $append = [];
            if (!empty($lines) && trim((string) end($lines)) !== '') {
                $append[] = '';
            }

            $append[] = '[' . strtoupper($section) . ']';
            return rtrim(implode($eol, array_merge($lines, $append, array_values($formatted))), "\r\n") . $eol;
        }

        $before = array_slice($lines, 0, $sectionStart + 1);
        $body = array_slice($lines, $sectionStart + 1, $sectionEnd - $sectionStart - 1);
        $after = array_slice($lines, $sectionEnd);
        $used = [];
        $newBody = [];

        foreach ($body as $line) {
            if (preg_match('/^\s*([A-Z0-9_.-]+)\s*=/i', (string) $line, $matches)) {
                $key = strtoupper($matches[1]);
                if (isset($formatted[$key])) {
                    $newBody[] = $formatted[$key];
                    $used[$key] = true;
                    continue;
                }
            }

            $newBody[] = $line;
        }

        foreach ($formatted as $key => $line) {
            if (!isset($used[$key])) {
                $newBody[] = $line;
            }
        }

        return rtrim(implode($eol, array_merge($before, $newBody, $after)), "\r\n") . $eol;
    }

    private function formatEnvValue(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        $value = str_replace(["\r", "\n"], '', (string) $value);
        if ($value === '') {
            return '';
        }

        if (preg_match('/^[A-Za-z0-9_:@.\/\\\\-]+$/', $value)) {
            return $value;
        }

        return '"' . str_replace(['\\', '"'], ['\\\\', '\"'], $value) . '"';
    }

    private function applyRuntimeCacheConfig(string $driver, array $redis): void
    {
        $stores = (array) Config::get('cache.stores', []);
        $stores['redis'] = $this->buildRedisStoreConfig($redis);

        Config::set([
            'default' => $driver,
            'stores' => $stores,
        ], 'cache');

        $cacheManager = $this->app->make('cache');
        if (method_exists($cacheManager, 'forgetDriver')) {
            $cacheManager->forgetDriver(self::SUPPORTED_DRIVERS);
        }
    }

    private function clearConfigCacheFiles(): void
    {
        $runtimePath = rtrim($this->app->getRootPath(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'runtime';
        $files = [$runtimePath . DIRECTORY_SEPARATOR . 'config.php'];

        if (is_dir($runtimePath)) {
            $children = scandir($runtimePath);
            foreach ($children === false ? [] : $children as $child) {
                if ($child === '.' || $child === '..') {
                    continue;
                }

                $path = $runtimePath . DIRECTORY_SEPARATOR . $child;
                if (is_dir($path)) {
                    $files[] = $path . DIRECTORY_SEPARATOR . 'config.php';
                }
            }
        }

        // optimize:config 会固化旧 .env 值，保存缓存配置后需要让下次请求重新加载配置文件。
        foreach ($files as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
    }

    private function checkCurrentCacheHealth(): array
    {
        try {
            $key = '__cache_health_check:' . bin2hex(random_bytes(4));
            Cache::set($key, 'ok', 5);
            Cache::delete($key);

            return [
                'available' => true,
                'message' => '',
            ];
        } catch (Throwable $e) {
            return [
                'available' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}
