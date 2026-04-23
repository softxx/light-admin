<?php

namespace core\service\crypto;

use app\Request;
use core\exception\FailedException;
use JsonException;
use Rtgm\sm\RtSm2;
use think\App;
use think\facade\Cache;

class TransportCryptoService
{
    private const REQUEST_AAD_PREFIX = 'REQ';
    private const RESPONSE_AAD_PREFIX = 'RES';
    private const META_SUITE = 1;

    private ?array $keyPair = null;

    public function __construct(private readonly App $app)
    {
    }

    public function isEnabled(): bool
    {
        $enabled = $this->config('enabled', true);
        if (is_bool($enabled)) {
            return $enabled;
        }

        return filter_var($enabled, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? true;
    }

    public function shouldBypass(Request $request): bool
    {
        if (!$this->isEnabled() || $request->isOptions() || $request->isHead()) {
            return true;
        }

        return in_array($this->resolveRequestPath($request), $this->config('whitelist', []), true);
    }

    public function resolveRequestPath(Request $request): string
    {
        return $this->normalizePath($request->requestPath());
    }

    public function getMeta(): array
    {
        $keyPair = $this->getKeyPair();

        return [
            'e' => $this->isEnabled() ? 1 : 0,
            'r' => (int) $this->config('protocol_version', 1),
            's' => self::META_SUITE,
            'k' => $keyPair['kid'],
            'p' => $keyPair['public_key'],
            'x' => 0,
            'a' => (int) $this->config('sm4.key_length', 16),
            'b' => (int) $this->config('sm4.iv_length', 16),
        ];
    }

    public function decryptRequest(Request $request): array
    {
        $path = $this->resolveRequestPath($request);
        $envelope = $this->parseRequestEnvelope($request);
        $this->validateEnvelope($envelope);
        $this->assertKeyVersion((string) $envelope['kid']);

        $timestamp = (int) $envelope['ts'];
        $nonce = (string) $envelope['nonce'];
        $this->assertTimestamp($timestamp);

        $iv = $this->decodeBase64Url((string) $envelope['iv']);
        $ciphertext = $this->decodeBase64Url((string) $envelope['ct']);
        $mac = strtolower((string) $envelope['mac']);
        $this->assertBinarySize($iv, (int) $this->config('sm4.iv_length', 16), 4604, 400, 'sm4 iv');
        $this->assertMac($mac);

        $sm4Key = $this->decryptSm4Key((string) $envelope['ek']);
        $aad = $this->buildRequestAad($request->method(), $path, $timestamp, $nonce);
        $expectedMac = $this->signPayload($sm4Key, $aad, $iv, $ciphertext);
        if (!hash_equals($expectedMac, $mac)) {
            throw new FailedException('encrypted payload verify failed', 4605, [], 401);
        }

        try {
            $plaintext = Sm4CbcCipher::decrypt($ciphertext, $sm4Key, $iv);
        } catch (\Throwable) {
            throw new FailedException('encrypted payload decrypt failed', 4605, [], 401);
        }

        try {
            $payload = json_decode($plaintext, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new FailedException('encrypted payload is invalid json', 4601, [], 400);
        }

        $data = $payload['data'] ?? $payload;
        if (!is_array($data)) {
            throw new FailedException('encrypted payload data must be an object', 4601, [], 400);
        }

        $this->consumeNonce($nonce);

        return [
            'data' => $data,
            'context' => [
                'path' => $path,
                'request_nonce' => $nonce,
                'kid' => (string) $envelope['kid'],
                'sm4_key' => $sm4Key,
            ],
        ];
    }

    public function encryptResponse(Request $request, array $payload): array
    {
        if (!$request->isEncryptedRequest()) {
            return $payload;
        }

        $sm4Key = $request->encryptedContext('sm4_key');
        if (!is_string($sm4Key) || strlen($sm4Key) !== (int) $this->config('sm4.key_length', 16)) {
            throw new \RuntimeException('SM4 response key is unavailable.');
        }

        try {
            $plaintext = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new \RuntimeException('Response payload json encode failed.', previous: $exception);
        }

        $path = (string) $request->encryptedContext('path', $this->resolveRequestPath($request));
        $requestNonce = (string) $request->encryptedContext('request_nonce', '');
        $timestamp = time();
        $responseNonce = $this->generateNonce();
        $iv = random_bytes((int) $this->config('sm4.iv_length', 16));
        $aad = $this->buildResponseAad($path, $requestNonce, $timestamp, $responseNonce);

        $ciphertext = Sm4CbcCipher::encrypt($plaintext, $sm4Key, $iv);
        $mac = $this->signPayload($sm4Key, $aad, $iv, $ciphertext);

        return [
            'v' => (int) $this->config('protocol_version', 1),
            'enc' => 1,
            'ts' => $timestamp,
            'nonce' => $responseNonce,
            'iv' => $this->encodeBase64Url($iv),
            'ct' => $this->encodeBase64Url($ciphertext),
            'mac' => $mac,
        ];
    }

    public function buildRequestAad(string $method, string $path, int $timestamp, string $nonce): string
    {
        return implode("\n", [
            self::REQUEST_AAD_PREFIX,
            strtoupper($method),
            $path,
            (string) $timestamp,
            $nonce,
        ]);
    }

    public function buildResponseAad(string $path, string $requestNonce, int $timestamp, string $nonce): string
    {
        return implode("\n", [
            self::RESPONSE_AAD_PREFIX,
            $path,
            $requestNonce,
            (string) $timestamp,
            $nonce,
        ]);
    }

    private function parseRequestEnvelope(Request $request): array
    {
        if ($request->isGet()) {
            $queryParam = (string) $this->config('request_query_param', '__enc');
            $encoded = (string) $request->get($queryParam, '');
            if ($encoded === '') {
                throw new FailedException('encrypted payload missing', 4600, [], 400);
            }

            try {
                $decoded = $this->decodeBase64Url($encoded);
                return json_decode($decoded, true, 512, JSON_THROW_ON_ERROR);
            } catch (\Throwable) {
                throw new FailedException('encrypted query payload is invalid', 4601, [], 400);
            }
        }

        $body = trim($request->getContent());
        if ($body === '') {
            throw new FailedException('encrypted payload missing', 4600, [], 400);
        }

        try {
            return json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new FailedException('encrypted payload is invalid json', 4601, [], 400);
        }
    }

    private function validateEnvelope(array $envelope): void
    {
        $requiredFields = ['v', 'kid', 'ts', 'nonce', 'ek', 'iv', 'ct', 'mac'];
        foreach ($requiredFields as $field) {
            if (!array_key_exists($field, $envelope) || $envelope[$field] === '') {
                throw new FailedException("encrypted payload field {$field} is required", 4600, [], 400);
            }
        }

        if (!array_key_exists('s', $envelope) || $envelope['s'] === '') {
            throw new FailedException('encrypted payload suite is required', 4600, [], 400);
        }

        if ((int) $envelope['v'] !== (int) $this->config('protocol_version', 1)) {
            throw new FailedException('encrypted payload version is invalid', 4601, [], 400);
        }

        if (!is_numeric($envelope['s']) || (int) $envelope['s'] !== self::META_SUITE) {
            throw new FailedException('encrypted payload algorithm is invalid', 4608, [], 415);
        }

        if (!is_numeric($envelope['ts'])) {
            throw new FailedException('encrypted payload timestamp is invalid', 4601, [], 400);
        }

        if (!is_string($envelope['nonce']) || strlen((string) $envelope['nonce']) < 16) {
            throw new FailedException('encrypted payload nonce is invalid', 4601, [], 400);
        }
    }

    private function assertTimestamp(int $timestamp): void
    {
        $skew = (int) $this->config('timestamp_ttl', 300);
        if (abs(time() - $timestamp) > $skew) {
            throw new FailedException('encrypted payload timestamp expired', 4606, [], 408);
        }
    }

    private function consumeNonce(string $nonce): void
    {
        $cacheKey = (string) $this->config('nonce_cache_prefix', 'transport_crypto_nonce:') . $nonce;
        if (Cache::has($cacheKey)) {
            throw new FailedException('encrypted payload nonce duplicated', 4607, [], 409);
        }

        Cache::set($cacheKey, 1, (int) $this->config('nonce_ttl', 300));
    }

    private function assertKeyVersion(string $kid): void
    {
        if ($kid !== $this->getKeyPair()['kid']) {
            throw new FailedException('encrypted payload kid is invalid', 4602, [], 400);
        }
    }

    private function decryptSm4Key(string $encryptedKey): string
    {
        $sm2 = new RtSm2();
        $plainKey = $sm2->doDecrypt($encryptedKey, $this->getKeyPair()['private_key'], true, 1);
        $this->assertBinarySize(
            $plainKey,
            (int) $this->config('sm4.key_length', 16),
            4603,
            400,
            'sm4 key'
        );

        return $plainKey;
    }

    private function signPayload(string $sm4Key, string $aad, string $iv, string $ciphertext): string
    {
        return Sm3Hasher::hmacHex(
            $this->buildMacPayload($aad, $iv, $ciphertext),
            $sm4Key
        );
    }

    private function buildMacPayload(string $aad, string $iv, string $ciphertext): string
    {
        return $this->packUint32(strlen($aad))
            . $aad
            . $this->packUint32(strlen($iv))
            . $iv
            . $this->packUint32(strlen($ciphertext))
            . $ciphertext;
    }

    private function assertMac(string $mac): void
    {
        $expectedLength = (int) $this->config('sm3.mac_hex_length', 64);
        if (
            strlen($mac) !== $expectedLength
            || !preg_match('/^[a-f0-9]+$/', $mac)
        ) {
            throw new FailedException('encrypted payload mac is invalid', 4604, [], 400);
        }
    }

    private function assertBinarySize(
        string $value,
        int $expectedSize,
        int $code,
        int $httpCode,
        string $label
    ): void {
        if (strlen($value) !== $expectedSize) {
            throw new FailedException("encrypted {$label} length is invalid", $code, [], $httpCode);
        }
    }

    private function getKeyPair(): array
    {
        if ($this->keyPair !== null) {
            return $this->keyPair;
        }

        $directory = $this->resolveKeyDirectory();
        $privatePath = $directory . DIRECTORY_SEPARATOR . (string) $this->config('private_key_file');
        $publicPath = $directory . DIRECTORY_SEPARATOR . (string) $this->config('public_key_file');

        if (!is_file($privatePath) || !is_file($publicPath)) {
            $this->generateAndPersistKeyPair($privatePath, $publicPath);
        }

        $privateKey = trim((string) file_get_contents($privatePath));
        $publicKey = trim((string) file_get_contents($publicPath));
        if ($privateKey === '' || $publicKey === '') {
            $this->generateAndPersistKeyPair($privatePath, $publicPath);
            $privateKey = trim((string) file_get_contents($privatePath));
            $publicKey = trim((string) file_get_contents($publicPath));
        }

        $this->keyPair = [
            'private_key' => $privateKey,
            'public_key' => $publicKey,
            'kid' => 'server-key-' . substr(hash('sha256', $publicKey), 0, 12),
        ];

        return $this->keyPair;
    }

    private function generateAndPersistKeyPair(string $privatePath, string $publicPath): void
    {
        $directory = dirname($privatePath);
        if (!is_dir($directory)) {
            force_mkdir($directory);
        }

        $sm2 = new RtSm2();
        [$privateKey, $publicKey] = $sm2->generatekey();

        file_put_contents($privatePath, $privateKey);
        file_put_contents($publicPath, $publicKey);
    }

    private function resolveKeyDirectory(): string
    {
        $configured = trim((string) $this->config('key_dir', 'crypto'), '\\/');
        return rtrim($this->app->getRuntimePath(), '\\/') . DIRECTORY_SEPARATOR . $configured;
    }

    private function generateNonce(): string
    {
        return bin2hex(random_bytes(16));
    }

    private function encodeBase64Url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function decodeBase64Url(string $value): string
    {
        $normalized = strtr($value, '-_', '+/');
        $padded = str_pad($normalized, (int) ceil(strlen($normalized) / 4) * 4, '=', STR_PAD_RIGHT);
        $decoded = base64_decode($padded, true);
        if ($decoded === false) {
            throw new FailedException('encrypted payload base64url decode failed', 4604, [], 400);
        }

        return $decoded;
    }

    private function normalizePath(string $path): string
    {
        $normalized = '/' . ltrim($path ?: '/', '/');
        return $normalized === '/' ? $normalized : rtrim($normalized, '/');
    }

    private function packUint32(int $value): string
    {
        return pack('N', $value);
    }

    private function config(string $key, mixed $default = null): mixed
    {
        return $this->app->config->get('crypto.' . $key, $default);
    }
}
