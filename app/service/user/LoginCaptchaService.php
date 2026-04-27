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
            'image' => $this->buildCaptchaImage($code),
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

    /**
     * 获取背景干扰直线数量。
     *
     * @return int
     */
    private function getNoiseLineCount(): int
    {
        return min(16, max(4, (int) config('login.captcha.noise_line_count', 8)));
    }

    /**
     * 获取背景干扰曲线数量。
     *
     * @return int
     */
    private function getNoiseCurveCount(): int
    {
        return min(10, max(1, (int) config('login.captcha.noise_curve_count', 3)));
    }

    /**
     * 获取噪点数量。
     *
     * @return int
     */
    private function getNoiseDotCount(): int
    {
        return min(48, max(10, (int) config('login.captcha.noise_dot_count', 24)));
    }

    /**
     * 获取覆盖在字符上的轻量干扰线数量。
     *
     * @return int
     */
    private function getNoiseOverlayLineCount(): int
    {
        return min(6, max(0, (int) config('login.captcha.noise_overlay_line_count', 2)));
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
     * Restore the previous captcha style by always returning the SVG variant.
     *
     * @param string $code
     * @return string
     */
    private function buildCaptchaImage(string $code): string
    {
        return 'data:image/svg+xml;base64,' . base64_encode($this->buildSvg($code));
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
        $filterId = 'captcha_distort_' . substr(bin2hex(random_bytes(4)), 0, 8);

        $svg = [
            sprintf(
                '<svg xmlns="http://www.w3.org/2000/svg" width="%d" height="%d" viewBox="0 0 %d %d" role="img" aria-label="captcha">',
                $width,
                $height,
                $width,
                $height
            ),
            $this->buildDistortionDefinition($filterId),
            sprintf(
                '<rect x="0.5" y="0.5" width="%d" height="%d" rx="2" fill="%s" stroke="%s" stroke-width="1"/>',
                $width - 1,
                $height - 1,
                $this->randomLightColor(),
                $this->randomBorderColor()
            ),
        ];

        // 先铺一层细碎底噪和划痕，让画布不再是完全规整的纯白背景。
        $this->appendNoiseLines($svg, $width, $height);
        $this->appendNoiseDots($svg, $width, $height);
        $this->appendAccentStrokes($svg, $width, $height, false, $filterId);

        // 字符本身改成更接近手写笔迹的双层绘制，再叠加轻微形变滤镜。
        $this->appendHandwrittenChars($svg, $chars, $charWidth, $height, $filterId);

        // 最后叠加彩色横划线和波浪线，风格更接近手写验证码截图。
        $this->appendNoiseCurves($svg, $width, $height);
        $this->appendOverlayNoiseLines($svg, $width, $height, $filterId);
        $this->appendAccentStrokes($svg, $width, $height, true, $filterId);

        $svg[] = '</svg>';

        return implode('', $svg);
    }

    /**
     * 追加背景直线干扰。
     *
     * @param array $svg
     * @param int $width
     * @param int $height
     * @return void
     */
    private function appendNoiseLines(array &$svg, int $width, int $height): void
    {
        for ($index = 0; $index < $this->getNoiseLineCount(); $index++) {
            $svg[] = sprintf(
                '<line x1="%d" y1="%d" x2="%d" y2="%d" stroke="%s" stroke-width="%s" stroke-opacity="%s"/>',
                random_int(0, $width),
                random_int(0, $height),
                random_int(0, $width),
                random_int(0, $height),
                $this->randomLineColor(),
                $this->randomStrokeWidth(6, 12),
                $this->randomOpacity(10, 20)
            );
        }
    }

    /**
     * 追加背景曲线干扰，让字符轮廓不再过于规则。
     *
     * @param array $svg
     * @param int $width
     * @param int $height
     * @return void
     */
    private function appendNoiseCurves(array &$svg, int $width, int $height): void
    {
        for ($index = 0; $index < $this->getNoiseCurveCount(); $index++) {
            $startY = random_int((int) ($height * 0.18), (int) ($height * 0.82));
            $endY = random_int((int) ($height * 0.18), (int) ($height * 0.82));
            $controlX = random_int((int) ($width * 0.2), (int) ($width * 0.8));
            $controlY = random_int(-8, $height + 8);

            $svg[] = sprintf(
                '<path d="M 0 %d Q %d %d %d %d" fill="none" stroke="%s" stroke-width="%s" stroke-opacity="%s" stroke-linecap="round"/>',
                $startY,
                $controlX,
                $controlY,
                $width,
                $endY,
                $this->randomAccentColor(),
                $this->randomStrokeWidth(8, 14),
                $this->randomOpacity(24, 38)
            );
        }
    }

    /**
     * 追加背景噪点，进一步打散颜色分布。
     *
     * @param array $svg
     * @param int $width
     * @param int $height
     * @return void
     */
    private function appendNoiseDots(array &$svg, int $width, int $height): void
    {
        for ($index = 0; $index < $this->getNoiseDotCount(); $index++) {
            $svg[] = sprintf(
                '<circle cx="%d" cy="%d" r="%d" fill="%s" fill-opacity="%s"/>',
                random_int(4, max(4, $width - 4)),
                random_int(4, max(4, $height - 4)),
                random_int(1, 2),
                $this->randomLineColor(),
                $this->randomOpacity(12, 22)
            );
        }
    }

    /**
     * 追加轻量覆盖线，让字符之间的边界更难被直接切分。
     *
     * @param array $svg
     * @param int $width
     * @param int $height
     * @return void
     */
    private function appendOverlayNoiseLines(array &$svg, int $width, int $height, string $filterId): void
    {
        for ($index = 0; $index < $this->getNoiseOverlayLineCount(); $index++) {
            $startX = random_int(0, (int) ($width * 0.12));
            $startY = random_int((int) ($height * 0.28), (int) ($height * 0.76));
            $controlX = random_int((int) ($width * 0.3), (int) ($width * 0.7));
            $controlY = random_int(0, $height);
            $endX = random_int((int) ($width * 0.88), $width);
            $endY = random_int((int) ($height * 0.3), (int) ($height * 0.8));

            $svg[] = sprintf(
                '<path d="M %d %d Q %d %d %d %d" fill="none" stroke="%s" stroke-width="%s" stroke-opacity="%s" stroke-linecap="round" filter="url(#%s)"/>',
                $startX,
                $startY,
                $controlX,
                $controlY,
                $endX,
                $endY,
                $this->randomAccentColor(),
                $this->randomStrokeWidth(10, 16),
                $this->randomOpacity(30, 44),
                $filterId
            );
        }
    }

    /**
     * 以双层笔触绘制字符，让轮廓更像手写和涂抹过的效果。
     *
     * @param array $svg
     * @param array $chars
     * @param float $charWidth
     * @param int $height
     * @param string $filterId
     * @return void
     */
    private function appendHandwrittenChars(
        array &$svg,
        array $chars,
        float $charWidth,
        int $height,
        string $filterId
    ): void {
        foreach ($chars as $index => $char) {
            $x = (int) (($index + 0.66) * $charWidth) + random_int(-2, 3);
            $y = random_int((int) ($height * 0.58), (int) ($height * 0.82));
            $fontSize = random_int((int) ($height * 0.56), (int) ($height * 0.72));
            $rotate = random_int(-16, 16);
            $skew = random_int(-18, 18) / 10;
            $shadowX = $x + random_int(0, 2);
            $shadowY = $y + random_int(0, 2);
            $escapedChar = htmlspecialchars($char, ENT_QUOTES);

            $svg[] = sprintf(
                '<g filter="url(#%s)"><text x="%d" y="%d" fill="%s" fill-opacity="0.28" font-size="%d" font-family="%s" font-style="italic" font-weight="700" transform="rotate(%d %d %d) skewX(%s)">%s</text><text x="%d" y="%d" fill="%s" stroke="%s" stroke-width="%s" paint-order="stroke" font-size="%d" font-family="%s" font-style="italic" font-weight="700" transform="rotate(%d %d %d) skewX(%s)">%s</text></g>',
                $filterId,
                $shadowX,
                $shadowY,
                $this->randomInkShadowColor(),
                $fontSize,
                $this->getHandwrittenFontFamily(),
                $rotate,
                $shadowX,
                $shadowY,
                number_format($skew + (random_int(-4, 4) / 10), 1, '.', ''),
                $escapedChar,
                $x,
                $y,
                $this->randomTextColor(),
                $this->randomTextStrokeColor(),
                $this->randomStrokeWidth(5, 9),
                $fontSize,
                $this->getHandwrittenFontFamily(),
                $rotate,
                $x,
                $y,
                number_format($skew, 1, '.', ''),
                $escapedChar
            );
        }
    }

    /**
     * 追加彩色划线，让整体观感更接近手写验证码截图。
     *
     * @param array $svg
     * @param int $width
     * @param int $height
     * @param bool $overlay
     * @param string $filterId
     * @return void
     */
    private function appendAccentStrokes(
        array &$svg,
        int $width,
        int $height,
        bool $overlay,
        string $filterId
    ): void {
        $strokeCount = $overlay ? 2 : 1;

        for ($index = 0; $index < $strokeCount; $index++) {
            $startY = $overlay
                ? random_int((int) ($height * 0.35), (int) ($height * 0.7))
                : random_int((int) ($height * 0.18), (int) ($height * 0.38));
            $endY = $overlay
                ? random_int((int) ($height * 0.3), (int) ($height * 0.72))
                : random_int((int) ($height * 0.2), (int) ($height * 0.42));

            $svg[] = sprintf(
                '<path d="M %d %d C %d %d %d %d %d %d" fill="none" stroke="%s" stroke-width="%s" stroke-opacity="%s" stroke-linecap="round" filter="url(#%s)"/>',
                random_int(0, 8),
                $startY,
                random_int((int) ($width * 0.18), (int) ($width * 0.34)),
                random_int(0, $height),
                random_int((int) ($width * 0.48), (int) ($width * 0.7)),
                random_int(0, $height),
                random_int((int) ($width * 0.88), $width),
                $endY,
                $this->randomAccentColor(),
                $overlay ? $this->randomStrokeWidth(9, 15) : $this->randomStrokeWidth(6, 11),
                $overlay ? $this->randomOpacity(34, 50) : $this->randomOpacity(20, 32),
                $filterId
            );
        }
    }

    private function randomLightColor(): string
    {
        return $this->toHexColor($this->randomPaperRgb());
    }

    /**
     * 返回接近手写验证码常见的边框颜色。
     *
     * @return string
     */
    private function randomBorderColor(): string
    {
        return $this->toHexColor($this->randomBorderRgb());
    }

    private function randomLineColor(): string
    {
        return $this->toHexColor($this->randomNoiseRgb());
    }

    /**
     * 返回强调划线使用的亮色系颜色。
     *
     * @return string
     */
    private function randomAccentColor(): string
    {
        return $this->toHexColor($this->randomAccentRgb());
    }

    private function randomTextColor(): string
    {
        return $this->toHexColor($this->randomInkRgb());
    }

    /**
     * 返回字符描边颜色，增强笔画厚薄不均的感觉。
     *
     * @return string
     */
    private function randomTextStrokeColor(): string
    {
        return $this->toHexColor($this->randomInkStrokeRgb());
    }

    /**
     * 返回字符阴影颜色，模拟笔迹叠画后的毛边。
     *
     * @return string
     */
    private function randomInkShadowColor(): string
    {
        return $this->toHexColor($this->randomInkShadowRgb());
    }

    /**
     * Palette used for off-white raster backgrounds.
     *
     * @return array
     */
    private function randomPaperRgb(): array
    {
        return [random_int(248, 255), random_int(248, 255), random_int(247, 254)];
    }

    /**
     * Palette used for light borders around the captcha.
     *
     * @return array
     */
    private function randomBorderRgb(): array
    {
        return [random_int(220, 234), random_int(220, 234), random_int(218, 232)];
    }

    /**
     * Palette used for low-priority background noise.
     *
     * @return array
     */
    private function randomNoiseRgb(): array
    {
        return [random_int(162, 206), random_int(168, 214), random_int(172, 220)];
    }

    /**
     * Palette used for stronger colored sweep lines.
     *
     * @return array
     */
    private function randomAccentRgb(): array
    {
        $palette = [
            [98, 192, 199],
            [112, 206, 216],
            [173, 92, 120],
            [192, 104, 136],
        ];

        return $palette[array_rand($palette)];
    }

    /**
     * Main ink palette for captcha characters.
     *
     * @return array
     */
    private function randomInkRgb(): array
    {
        return [random_int(110, 148), random_int(52, 90), random_int(76, 116)];
    }

    /**
     * Stroke palette used to roughen character edges.
     *
     * @return array
     */
    private function randomInkStrokeRgb(): array
    {
        return [random_int(124, 164), random_int(58, 96), random_int(82, 124)];
    }

    /**
     * Shadow palette used to fake layered ink strokes.
     *
     * @return array
     */
    private function randomInkShadowRgb(): array
    {
        return [random_int(150, 188), random_int(82, 118), random_int(104, 142)];
    }

    /**
     * 获取更偏手写风格的字体族，尽量靠近示例图的观感。
     *
     * @return string
     */
    private function getHandwrittenFontFamily(): string
    {
        return 'Segoe Print, Bradley Hand ITC, Comic Sans MS, cursive';
    }

    /**
     * Convert an RGB triplet to a CSS hex color string.
     *
     * @param array $rgb
     * @return string
     */
    private function toHexColor(array $rgb): string
    {
        return sprintf(
            '#%02X%02X%02X',
            max(0, min(255, (int) ($rgb[0] ?? 0))),
            max(0, min(255, (int) ($rgb[1] ?? 0))),
            max(0, min(255, (int) ($rgb[2] ?? 0)))
        );
    }

    /**
     * 生成形变滤镜，让字符和干扰线带一点抖动感。
     *
     * @param string $filterId
     * @return string
     */
    private function buildDistortionDefinition(string $filterId): string
    {
        return sprintf(
            '<defs><filter id="%s" x="-12%%" y="-18%%" width="124%%" height="136%%"><feTurbulence type="fractalNoise" baseFrequency="%s %s" numOctaves="1" seed="%d" result="noise"/><feDisplacementMap in="SourceGraphic" in2="noise" scale="%s" xChannelSelector="R" yChannelSelector="G"/></filter></defs>',
            $filterId,
            number_format(random_int(12, 18) / 1000, 3, '.', ''),
            number_format(random_int(48, 72) / 1000, 3, '.', ''),
            random_int(1, 999),
            number_format(random_int(14, 24) / 10, 1, '.', '')
        );
    }

    /**
     * 生成 SVG 透明度值。
     *
     * @param int $minPercent
     * @param int $maxPercent
     * @return string
     */
    private function randomOpacity(int $minPercent, int $maxPercent): string
    {
        return number_format(random_int($minPercent, $maxPercent) / 100, 2, '.', '');
    }

    /**
     * 生成 SVG 线宽值。
     *
     * @param int $minTenths
     * @param int $maxTenths
     * @return string
     */
    private function randomStrokeWidth(int $minTenths, int $maxTenths): string
    {
        return number_format(random_int($minTenths, $maxTenths) / 10, 1, '.', '');
    }
}
