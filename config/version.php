<?php

return [
    // 当前应用版本信息。发布新版本时应和前端 VITE_VERSION 保持一致。
    'version' => '1.0.0',
    'build' => '20260424.1',
    'commit' => '',
    'released_at' => '2026-04-24 00:00:00',

    // 远程发行版平台配置。source 支持 github、gitlab、gitee、cnb，统一按 tag 和 assets 匹配升级包。
    'release' => [
        'app' => env('upgrade.app', 'light-admin'),
        'channel' => env('upgrade.channel', 'stable'),
        'source' => env('upgrade.source', 'github'),
        'owner' => env('upgrade.owner', env('upgrade.github_owner', '')),
        'repo' => env('upgrade.repo', env('upgrade.github_repo', '')),
        'project' => env('upgrade.project', ''),
        'api_base' => env('upgrade.api_base', ''),
        'asset_pattern' => env('upgrade.asset_pattern', env('upgrade.github_asset_pattern', 'light-admin-{version}.zip')),
        'release_token' => env('upgrade.release_token', ''),
        'release_token_header' => env('upgrade.release_token_header', ''),
        'github_owner' => env('upgrade.github_owner', ''),
        'github_repo' => env('upgrade.github_repo', ''),
        'github_token' => env('upgrade.github_token', ''),
        'github_api_base' => env('upgrade.github_api_base', 'https://api.github.com'),
        'github_asset_pattern' => env('upgrade.github_asset_pattern', 'light-admin-{version}.zip'),
        'include_prerelease' => env('upgrade.include_prerelease', false),
        'timeout' => (int) env('upgrade.timeout', 60),
        'allowed_hosts' => array_values(array_filter(array_map('trim', explode(',', (string) env('upgrade.allowed_hosts', ''))))),
    ],

    // 升级过程使用的本地工作目录和维护模式锁文件。
    'paths' => [
        'work_dir' => 'runtime/upgrade',
        'maintenance_lock' => 'runtime/maintenance.lock',
    ],
];
