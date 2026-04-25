<?php

namespace app;

use app\service\user\AuthService;
use core\exception\FailedException;
use Spatie\Macroable\Macroable;

// 应用请求对象类
class Request extends \think\Request
{
    use Macroable;

    protected $filter = ['htmlspecialchars', 'trim'];
    protected array $encryptedContext = [
        'encrypted' => false,
    ];

    /**
     * 当前登录的后台用户
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
