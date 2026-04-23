<?php

namespace app;

use app\service\user\AuthService;
use core\exception\FailedException;
use Spatie\Macroable\Macroable;

// 搴旂敤璇锋眰瀵硅薄绫?
class Request extends \think\Request
{
    use Macroable;

    protected $filter = ['htmlspecialchars', 'trim'];
    protected array $encryptedContext = [
        'encrypted' => false,
    ];

    /**
     * 褰撳墠鐧诲綍鐨勫悗鍙扮敤鎴?
     *
     * @return mixed
     */
    public function user()
    {
        try {
            $user = app()->make(AuthService::class)->user();
        } catch (\Exception $e) {
            throw new FailedException($e->getMessage(), httpCode: 401);
        }

        return $user;
    }

    public function replaceInputData(array $data): static
    {
        $this->param = [];
        $this->mergeParam = false;

        if ($this->isGet()) {
            $this->withGet($data);
            return $this;
        }

        $payload = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';
        $this->withInput($payload);
        $this->withPost($data);
        $this->put = $data;

        return $this;
    }

    public function markEncryptedRequest(bool $encrypted, array $context = []): static
    {
        $this->encryptedContext = array_merge(
            [
                'encrypted' => $encrypted,
            ],
            $context
        );

        return $this;
    }

    public function isEncryptedRequest(): bool
    {
        return (bool) ($this->encryptedContext['encrypted'] ?? false);
    }

    public function encryptedContext(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->encryptedContext;
        }

        return $this->encryptedContext[$key] ?? $default;
    }

    public function requestPath(): string
    {
        $requestUri = $this->server('REQUEST_URI', '');
        if (!empty($requestUri)) {
            $path = parse_url($requestUri, PHP_URL_PATH);
            if (is_string($path) && $path !== '') {
                return $path;
            }
        }

        return '/' . ltrim((string) $this->pathinfo(), '/');
    }
}
