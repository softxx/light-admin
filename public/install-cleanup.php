<?php

declare(strict_types=1);

use core\exception\FailedException;
use core\install\InstallCleanupService;

$rootPath = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR;

require_once $rootPath . 'vendor/autoload.php';

$app = new \think\App($rootPath);
$app->initialize();
$app->boot();

header('Content-Type: application/json; charset=utf-8');

function install_cleanup_response(int $code, string $msg, array $data = [], int $httpStatus = 200): never
{
    http_response_code($httpStatus);
    echo json_encode([
        'code' => $code,
        'msg' => $msg,
        'time' => time(),
        'data' => $data,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
    install_cleanup_response(0, '只支持 POST 请求', [], 405);
}

$raw = file_get_contents('php://input');
$payload = is_string($raw) && trim($raw) !== '' ? json_decode($raw, true) : [];
if (!is_array($payload)) {
    $payload = [];
}

$token = trim((string) ($payload['token'] ?? ($_POST['token'] ?? '')));

try {
    /** @var InstallCleanupService $cleanupService */
    $cleanupService = app()->make(InstallCleanupService::class);
    install_cleanup_response(1, '安装目录清理完成', $cleanupService->cleanup($token));
} catch (FailedException $exception) {
    install_cleanup_response(0, $exception->getMessage(), [], $exception->getHttpCode());
} catch (Throwable $exception) {
    install_cleanup_response(0, '安装目录清理失败: ' . $exception->getMessage(), [], 500);
}
