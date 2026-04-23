<?php

namespace app\service\user;

use think\facade\Cache;
use think\facade\Request;

class LoginLimitService
{
    protected $cachePrefix = 'login_attempts_';
    protected $maxAttempts = 10;
    protected $lockoutTime = 600; // 单位为秒，即10分钟

    public function __construct(?int $maxAttempts = null, ?int $lockoutTime = null)
    {
        $this->maxAttempts = max(1, $maxAttempts ?? (int) config('login.limit.max_attempts', 10));
        $this->lockoutTime = max(60, $lockoutTime ?? (int) config('login.limit.lockout_seconds', 600));
    }

    /**
     * 检查是否需要锁定
     *
     * @param string $username 用户名
     * @return bool
     */
    public function checkLockout(string $username): bool
    {
        return $this->getRemainingLockoutSeconds($username) > 0;
    }

    /**
     * adaptive 模式下，根据失败次数判断是否需要验证码。
     *
     * @param string $username 用户名
     * @param int|null $requiredAfterAttempts 触发阈值
     * @return bool
     */
    public function shouldRequireCaptcha(string $username, ?int $requiredAfterAttempts = null): bool
    {
        $requiredAfterAttempts = $requiredAfterAttempts ?? (int) config('login.captcha.required_after_attempts', 3);
        $requiredAfterAttempts = min(max(0, $requiredAfterAttempts), max(0, $this->maxAttempts - 1));

        return $this->getFailedAttempts($username) >= $requiredAfterAttempts;
    }

    /**
     * 获取当前用户名在当前 IP 下的失败次数。
     *
     * @param string $username 用户名
     * @return int
     */
    public function getFailedAttempts(string $username): int
    {
        return (int) ($this->getAttemptsInfo($username)['count'] ?? 0);
    }

    /**
     * 获取剩余锁定秒数。
     *
     * @param string $username 用户名
     * @return int
     */
    public function getRemainingLockoutSeconds(string $username): int
    {
        $attemptsInfo = $this->getAttemptsInfo($username);
        $count = (int) ($attemptsInfo['count'] ?? 0);
        $lastAttemptTime = (int) ($attemptsInfo['last_attempt_time'] ?? 0);

        if ($count < $this->maxAttempts || $lastAttemptTime <= 0) {
            return 0;
        }

        return max(0, $this->lockoutTime - (time() - $lastAttemptTime));
    }

    /**
     * 记录一次失败的登录尝试
     *
     * @param string $username 用户名
     */
    public function recordFailedAttempt(string $username): void
    {
        $ip = Request::ip();
        $cacheKey = $this->getCacheKey($ip, $username);
        $attemptsInfo = Cache::get($cacheKey) ?: ['count' => 0, 'last_attempt_time' => time()];

        $attemptsInfo['count'] += 1;
        $attemptsInfo['last_attempt_time'] = time();

        Cache::set($cacheKey, $attemptsInfo, $this->lockoutTime);
    }

    /**
     * 清除登录尝试记录
     *
     * @param string $username 用户名
     */
    public function clearAttempts(string $username): void
    {
        $ip = Request::ip();
        $cacheKey = $this->getCacheKey($ip, $username);
        Cache::delete($cacheKey);
    }

    /**
     * 获取缓存中的失败信息。
     *
     * @param string $username 用户名
     * @return array
     */
    protected function getAttemptsInfo(string $username): array
    {
        $ip = Request::ip();
        $attemptsInfo = Cache::get($this->getCacheKey($ip, $username));

        return is_array($attemptsInfo) ? $attemptsInfo : [];
    }

    /**
     * 获取缓存键名
     *
     * @param string $ip
     * @param string $username
     * @return string
     */
    protected function getCacheKey(string $ip, string $username): string
    {
        return $this->cachePrefix . md5($ip . '_' . $username);
    }
}
