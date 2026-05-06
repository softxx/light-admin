<?php

namespace app\service\user;

use app\model\system\User;
use core\base\BaseService;
use core\exception\FailedException;
use core\service\jwt\Factory;

class AuthService extends BaseService
{
    protected $user = null;
    protected $jwtAuth;
    protected LoginLimitService $loginLimiter;
    protected LoginCaptchaService $loginCaptcha;

    public function __construct()
    {
        parent::__construct();
        $this->jwtAuth = Factory::getInstance();
        $this->loginLimiter = new LoginLimitService();
        $this->loginCaptcha = new LoginCaptchaService();
    }

    /**
     * 后台登录。
     *
     * @param string $username
     * @param string $password
     * @param string|null $captchaId
     * @param string|null $captchaCode
     * @return array
     */
    public function login(
        string $username,
        string $password,
        ?string $captchaId = null,
        ?string $captchaCode = null
    ): array
    {
        if ($this->loginLimiter->checkLockout($username)) {
            $remainingMinutes = max(
                1,
                (int) ceil($this->loginLimiter->getRemainingLockoutSeconds($username) / 60)
            );
            throw new FailedException("由于多次输入错误密码，请{$remainingMinutes}分钟后重试");
        }

        if ($this->loginCaptcha->shouldRequire($username, $this->loginLimiter)) {
            $this->loginCaptcha->validate($captchaId, $captchaCode);
        }

        // 登录只校验账号状态；角色已移除，不再按角色状态拦截登录。
        $user = User::field('status,id,password')->getByUsername($username);

        if (!$user || !password_verify($password, $user->password)) {
            $this->loginLimiter->recordFailedAttempt($username);
            throw new FailedException('用户名或密码不正确');
        }

        $this->loginLimiter->clearAttempts($username);

        if ($user->status == User::DISABLE) {
            throw new FailedException('你的账号已被禁用，请与管理员联系');
        }

        User::update([
            'last_login_time' => time(),
            'last_login_ip' => request()->ip()
        ], ['id' => $user->id]);

        event('LoginLog', [$username, $user->id]);

        return $this->jwtAuth->generateToken(['id' => $user->id]);
    }

    /**
     * 获取当前用户。
     *
     * @return User|null
     */
    public function user(): ?User
    {
        if (is_null($this->user)) {
            // 权限判断只需要用户 ID 和 is_admin，避免加载已移除的角色/部门关系。
            $this->user = User::where('id', request()->uid())->field('id,is_admin')->find();
        }
        return $this->user;
    }

    /**
     * 退出登录。
     *
     * @param string $refreshToken
     */
    public function logout(string $refreshToken): void
    {
        $refreshToken = str_replace('Bearer ', '', $refreshToken);
        $accessToken = $this->jwtAuth->getToken();
        try {
            $this->jwtAuth->verifyAccessToken();
            $this->jwtAuth->addBlacklist($accessToken);
        } catch (\Exception) {}

        try {
            $this->jwtAuth->verifyRefreshToken($refreshToken);
            $this->jwtAuth->addBlacklist($refreshToken);
        } catch (\Exception) {}
    }

    /**
     * 刷新令牌。
     *
     * @return array
     */
    public function refreshToken(): array
    {
        $token = $this->jwtAuth->getToken();
        return $this->jwtAuth->refreshToken($token);
    }
}
