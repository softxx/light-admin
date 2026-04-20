<?php

declare(strict_types=1);

use core\exception\FailedException;
use core\install\EnvironmentChecker;
use core\install\InstallGuard;
use core\install\InstallService;

require __DIR__ . '/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

function installer_json_response(int $code, string $msg, array $data = [], int $httpStatus = 200): never
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

function installer_request_payload(): array
{
    $raw = file_get_contents('php://input');
    if (is_string($raw) && trim($raw) !== '') {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            return $decoded;
        }
    }

    return is_array($_POST) ? $_POST : [];
}

try {
    /** @var InstallGuard $guard */
    $guard = app()->make(InstallGuard::class);
    if ($guard->isInstalled()) {
        installer_json_response(0, '系统已安装，安装入口已关闭', [], 404);
    }

    $action = strtolower((string) ($_GET['action'] ?? 'status'));
    $payload = installer_request_payload();

    switch ($action) {
        case 'status':
            installer_json_response(1, '安装器准备就绪', app()->make(InstallService::class)->getBootstrapPayload());
            break;

        case 'check-env':
            /** @var EnvironmentChecker $checker */
            $checker = app()->make(EnvironmentChecker::class);
            installer_json_response(1, '环境检查完成', $checker->check());
            break;

        case 'check-db':
            /** @var InstallService $service */
            $service = app()->make(InstallService::class);
            installer_json_response(1, '数据库检查完成', $service->checkDatabase($payload));
            break;

        case 'run':
            /** @var InstallService $service */
            $service = app()->make(InstallService::class);
            installer_json_response(1, '安装完成', $service->install($payload));
            break;

        default:
            installer_json_response(0, '未知的安装操作', [], 404);
    }
} catch (FailedException $exception) {
    installer_json_response(0, $exception->getMessage(), [], $exception->getHttpCode());
} catch (Throwable $exception) {
    installer_json_response(0, '安装器执行失败: ' . $exception->getMessage(), [], 500);
}
