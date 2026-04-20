<?php

declare(strict_types=1);

namespace core\install;

use core\exception\FailedException;

class EnvironmentChecker
{
    public function check(): array
    {
        $items = [];
        $items[] = $this->makeCheck(
            'php_version',
            'PHP >= 8.2',
            version_compare(PHP_VERSION, '8.2.0', '>='),
            '当前版本: ' . PHP_VERSION
        );

        foreach ((array) config('install.required_extensions', []) as $extension) {
            $loaded = extension_loaded((string) $extension);
            $items[] = $this->makeCheck(
                'ext_' . $extension,
                '扩展 ' . $extension,
                $loaded,
                $loaded ? '已加载' : '未加载'
            );
        }

        $items[] = $this->checkFileReadable(
            'sql_file',
            '安装 SQL 文件',
            (string) config('install.sql_file')
        );
        $items[] = $this->checkFileWritable(
            'env_file',
            '.env 配置文件',
            app()->getRootPath() . '.env'
        );
        $items[] = $this->checkPathCreatable(
            'meta_path',
            '安装状态目录',
            (string) config('install.meta_path')
        );
        $items[] = $this->checkPathCreatable(
            'storage_path',
            '上传目录',
            app()->getRootPath() . 'public/storage'
        );

        $passed = !array_filter($items, static fn(array $item) => $item['ok'] === false);

        return [
            'passed' => $passed,
            'items' => $items,
        ];
    }

    public function assertPasses(): array
    {
        $result = $this->check();

        if (!$result['passed']) {
            throw new FailedException('环境检查未通过，请先修复缺失项');
        }

        return $result;
    }

    private function makeCheck(string $key, string $label, bool $ok, string $message): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'ok' => $ok,
            'message' => $message,
        ];
    }

    private function checkFileReadable(string $key, string $label, string $path): array
    {
        return $this->makeCheck(
            $key,
            $label,
            is_file($path) && is_readable($path),
            is_file($path) && is_readable($path) ? '可读取' : '文件不存在或不可读取'
        );
    }

    private function checkFileWritable(string $key, string $label, string $path): array
    {
        $exists = is_file($path);
        $ok = ($exists && is_writable($path)) || (!$exists && is_writable(dirname($path)));

        return $this->makeCheck(
            $key,
            $label,
            $ok,
            $ok ? '可写入' : '无法写入'
        );
    }

    private function checkPathCreatable(string $key, string $label, string $path): array
    {
        $ok = is_dir($path) ? is_writable($path) : is_writable(dirname($path));

        return $this->makeCheck(
            $key,
            $label,
            $ok,
            $ok ? '可创建 / 可写入' : '目录不可写'
        );
    }
}
