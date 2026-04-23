<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2019 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

// [ 应用入口文件 ]
namespace think;

use core\install\InstallGuard;
use Throwable;

require __DIR__ . '/../vendor/autoload.php';

$rootPath = dirname(__DIR__) . DIRECTORY_SEPARATOR;

if (isHomeRequest()) {
    $installState = resolveInstallState($rootPath);

    if ($installState === false) {
        header('Location: ' . resolveInstallUrl(), true, 302);
        exit;
    }

    if ($installState === true && shouldServeSpaEntry()) {
        $spaEntryFile = resolveSpaEntryFile($rootPath);
        if ($spaEntryFile !== null) {
            outputSpaEntry($spaEntryFile);
            exit;
        }
    }
}

// 执行HTTP应用并响应
$http = (new App($rootPath))->http;

$response = $http->run();

$response->send();

$http->end($response);

function resolveInstallState(string $rootPath): ?bool
{
    try {
        $app = new App($rootPath);
        $app->initialize();
        $app->boot();

        /** @var InstallGuard $guard */
        $guard = $app->make(InstallGuard::class);

        return $guard->isInstalled();
    } catch (Throwable) {
        return null;
    }
}

function isHomeRequest(): bool
{
    return normalizeRequestPath() === '/';
}

function normalizeRequestPath(): string
{
    $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
    $path = (string) (parse_url($uri, PHP_URL_PATH) ?: '/');
    $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));

    if ($scriptName !== '' && str_starts_with($path, $scriptName)) {
        $path = substr($path, strlen($scriptName)) ?: '/';
    }

    $scriptDir = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
    if ($scriptDir !== '' && $scriptDir !== '.' && str_starts_with($path, $scriptDir . '/')) {
        $path = substr($path, strlen($scriptDir)) ?: '/';
    }

    $normalized = '/' . ltrim($path, '/');

    return $normalized === '//' ? '/' : $normalized;
}

function resolveInstallUrl(): string
{
    $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
    $basePath = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
    $basePath = $basePath === '.' ? '' : $basePath;

    return ($basePath !== '' ? $basePath : '') . '/install/';
}

function shouldServeSpaEntry(): bool
{
    $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));

    return $method === 'GET' || $method === 'HEAD';
}

function resolveSpaEntryFile(string $rootPath): ?string
{
    $candidates = [
        __DIR__ . DIRECTORY_SEPARATOR . 'app.html',
        __DIR__ . DIRECTORY_SEPARATOR . 'index.html',
    ];

    foreach ($candidates as $candidate) {
        if (is_file($candidate)) {
            return $candidate;
        }
    }

    return null;
}

function outputSpaEntry(string $file): void
{
    if (!headers_sent()) {
        header('Content-Type: text/html; charset=UTF-8');
    }

    if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'HEAD') {
        return;
    }

    readfile($file);
}
