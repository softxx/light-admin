<?php

declare(strict_types=1);

$rootPath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR;

require_once $rootPath . 'vendor/autoload.php';

$app = new \think\App($rootPath);
$app->initialize();
$app->boot();

return $app;
