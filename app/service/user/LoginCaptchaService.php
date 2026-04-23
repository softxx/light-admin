<?php

namespace app\service\user;

use core\exception\FailedException;
use think\facade\Cache;
use think\helper\Str;

class LoginCaptchaService
{
    public const CAPTCHA_REQUIRED_CODE = 4301;
    public const CAPTCHA_INVALID_CODE = 4302;
    public const MODE_ALWAYS = 'always';
    public const MODE_ADAPTIVE = 'adaptive';

    private string $cachePrefix = 'login_captcha:';

    /**
     * 验证码功能总开关。
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        $enabled = config('login.captcha.enabled', true);
        if (is_bool($enabled)) {
            return $enabled;
        }

        return filter_var($enabled, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? true;
    }

    /**
     * 对前端暴露当前验证码配置，让登录页决定是否默认展示。
     *
     * @return array
     */
    public function meta(): array
    {
        return [
            'enabled' => $this->isEnabled(),
            'mode' => $this->getMode(),
            'requiredAfterAttempts' => $this->getRequiredAfterAttempts(),
        ];
    }

    /**
     * 登录页初始化使用，一次返回展示配置和首张验证码。
     *
     * @return array
     */
    public function bootstrap(): array
    {
        $meta = $this->meta();
        $payload = [
            'meta' => $meta,
        ];

        if ($meta['enabled'] && $meta['mode'] === self::MODE_ALWAYS) {
            $payload['captcha'] = $this->issue();
        }

        return $payload;
    }

    /**
     * 判断当前登录请求是否需要验证码。
     *
     * @param string $username 用户名
     * @param LoginLimitService $limiter 登录限流服务
     * @return bool
     */
    public function shouldRequire(string $username, LoginLimitService $limiter): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        if ($this->getMode() === self::MODE_ALWAYS) {
            return true;
        }

        return $limiter->shouldRequireCaptcha($username, $this->getRequiredAfterAttempts());
    }

    /**
     * 生成新的验证码图片。
     *
     * @return array
     */
    public function issue(): array
    {
        $this->assertEnabled();

        $code = $this->generateCode();
        $captchaId = bin2hex(random_bytes(16));

        Cache::set($this->getCacheKey($captchaId), [
            'hash' => $this->hashCode($code),
            'ip' => request()->ip(),
        ], $this->getTtl());

        return [
            'captchaId' => $captchaId,
            'image' => 'data:image/svg+xml;base64,' . base64_encode($this->buildSvg($code)),
            'expireIn' => $this->getTtl(),
        ];
    }

    /**
     * 校验验证码，校验后立即销毁，避免重复使用。
     *
     * @param string|null $captchaId 验证码标识
     * @param string|null $captchaCode 用户输入的验证码
     * @return void
     */
    public function validate(?string $captchaId, ?string $captchaCode): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        $captchaId = trim((string) $captchaId);
        $captchaCode = trim((string) $captchaCode);

        if ($captchaId === '' || $captchaCode === '') {
            $this->throwCaptchaRequired('请输入验证码');
        }

        $cacheKey = $this->getCacheKey($captchaId);
        $payload = Cache::get($cacheKey);
        Cache::delete($cacheKey);

        if (!is_array($payload)) {
            $this->throwCaptchaInvalid('验证码已过期，请重新获取');
        }

        if (($payload['ip'] ?? '') !== request()->ip()) {
            $this->throwCaptchaInvalid('验证码已失效，请重新获取');
        }

        if (!hash_equals((string) ($payload['hash'] ?? ''), $this->hashCode($captchaCode))) {
            $this->throwCaptchaInvalid('验证码错误，请重新输入');
        }
    }

    /**
     * 获取验证码展示模式。
     *
     * @return string
     */
    public function getMode(): string
    {
        $mode = strtolower(trim((string) config('login.captcha.mode', self::MODE_ALWAYS)));

        return in_array($mode, [self::MODE_ALWAYS, self::MODE_ADAPTIVE], true)
            ? $mode
            : self::MODE_ALWAYS;
    }

    /**
     * 获取 adaptive 模式下的触发阈值。
     *
     * @return int
     */
    public function getRequiredAfterAttempts(): int
    {
        return max(0, (int) config('login.captcha.required_after_attempts', 3));
    }

    private function assertEnabled(): void
    {
        if (!$this->isEnabled()) {
            throw new FailedException('登录验证码未开启');
        }
    }

    private function getTtl(): int
    {
        return max(30, (int) config('login.captcha.ttl', 120));
    }

    private function getLength(): int
    {
        return min(6, max(4, (int) config('login.captcha.length', 4)));
    }

    private function getWidth(): int
    {
        return max(100, (int) config('login.captcha.width', 130));
    }

    private function getHeight(): int
    {
        return max(36, (int) config('login.captcha.height', 40));
    }

    private function getCacheKey(string $captchaId): string
    {
        return $this->cachePrefix . $captchaId;
    }

    /**
     * 生成验证码字符，使用大写字母和数字降低识别歧义。
     *
     * @return string
     */
    private function generateCode(): string
    {
        return Str::upper(Str::random($this->getLength(), 2, '23456789'));
    }

    private function hashCode(string $code): string
    {
        $secret = (string) config('system.secret_key', 'light-admin');
        return hash('sha256', Str::upper($code) . '|' . $secret);
    }

    private function throwCaptchaRequired(string $message): never
    {
        throw new FailedException($message, self::CAPTCHA_REQUIRED_CODE, $this->buildFailureData());
    }

    private function throwCaptchaInvalid(string $message): never
    {
        throw new FailedException($message, self::CAPTCHA_INVALID_CODE, $this->buildFailureData());
    }

    /**
     * 验证失败时带回新的验证码，前端可直接刷新显示。
     *
     * @return array
     */
    private function buildFailureData(): array
    {
        return [
            'needCaptcha' => true,
            'captcha' => $this->issue(),
            'captchaMeta' => $this->meta(),
        ];
    }

    /**
     * 使用 SVG 生成轻量验证码图片，避免额外 GD 依赖。
     *
     * @param string $code 验证码内容
     * @return string
     */
    private function buildSvg(string $code): string
    {
        $width = $this->getWidth();
        $height = $this->getHeight();
        $chars = str_split($code);
        $charWidth = $width / (count($chars) + 1);

        $svg = [
            sprintf(
                '<svg xmlns="http://www.w3.org/2000/svg" width="%d" height="%d" viewBox="0 0 %d %d" role="img" aria-label="captcha">',
                $width,
                $height,
                $width,
                $height
            ),
            sprintf('<rect width="100%%" height="100%%" rx="8" fill="%s"/>', $this->randomLightColor()),
        ];

        // 干扰线。
        for ($index = 0; $index < 5; $index++) {
            $svg[] = sprintf(
                '<line x1="%d" y1="%d" x2="%d" y2="%d" stroke="%s" stroke-width="1.2" stroke-opacity="0.45"/>',
                random_int(0, $width),
                random_int(0, $height),
                random_int(0, $width),
                random_int(0, $height),
                $this->randomLineColor()
            );
        }

        // 干扰点。
        for ($index = 0; $index < 18; $index++) {
            $svg[] = sprintf(
                '<circle cx="%d" cy="%d" r="%d" fill="%s" fill-opacity="0.35"/>',
                random_int(4, max(4, $width - 4)),
                random_int(4, max(4, $height - 4)),
                random_int(1, 2),
                $this->randomLineColor()
            );
        }

        // 验证码字符。
        foreach ($chars as $index => $char) {
            $x = (int) (($index + 0.72) * $charWidth) + random_int(-2, 2);
            $y = random_int((int) ($height * 0.62), (int) ($height * 0.82));
            $fontSize = random_int((int) ($height * 0.52), (int) ($height * 0.66));
            $rotate = random_int(-20, 20);

            $svg[] = sprintf(
                '<text x="%d" y="%d" fill="%s" font-size="%d" font-family="Arial, sans-serif" font-weight="700" transform="rotate(%d %d %d)">%s</text>',
                $x,
                $y,
                $this->randomTextColor(),
                $fontSize,
                $rotate,
                $x,
                $y,
                htmlspecialchars($char, ENT_QUOTES)
            );
        }

        $svg[] = '</svg>';

        return implode('', $svg);
    }

    private function randomLightColor(): string
    {
        return sprintf('#%02X%02X%02X', random_int(235, 250), random_int(240, 252), random_int(245, 255));
    }

    private function randomLineColor(): string
    {
        return sprintf('#%02X%02X%02X', random_int(120, 190), random_int(130, 210), random_int(140, 220));
    }

    private function randomTextColor(): string
    {
        return sprintf('#%02X%02X%02X', random_int(40, 110), random_int(60, 130), random_int(80, 160));
    }
}
