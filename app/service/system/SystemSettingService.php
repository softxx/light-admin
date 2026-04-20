<?php

namespace app\service\system;

use app\model\system\SystemSetting;
use core\base\BaseService;

class SystemSettingService extends BaseService
{
    private const DEFAULT_SETTING = [
        'system_name' => 'Art Design Pro',
        'logo' => '',
        'favicon' => '',
        'homepage_enabled' => 1,
        'homepage_title' => '项目管理平台',
        'homepage_intro' => '这里先作为项目的简洁首页，展示项目介绍与后台入口。后续可以在系统设置中继续调整文案，不需要每次修改源码。'
    ];

    public function __construct(SystemSetting $model)
    {
        $this->model = $model;
    }

    public function getSetting(): array
    {
        $setting = $this->model->order('id', 'asc')->find();

        if (!$setting) {
            return self::DEFAULT_SETTING;
        }

        return array_merge(
            self::DEFAULT_SETTING,
            array_pick('system_name,logo,favicon,homepage_enabled,homepage_title,homepage_intro', $setting->toArray())
        );
    }

    public function update(array $data): array
    {
        $payload = [
            'system_name' => trim($data['system_name'] ?? '') ?: self::DEFAULT_SETTING['system_name'],
            'logo' => trim($data['logo'] ?? ''),
            'favicon' => trim($data['favicon'] ?? ''),
            'homepage_enabled' => (int)($data['homepage_enabled'] ?? self::DEFAULT_SETTING['homepage_enabled']) === 0 ? 0 : 1,
            'homepage_title' => trim($data['homepage_title'] ?? '') ?: self::DEFAULT_SETTING['homepage_title'],
            'homepage_intro' => trim($data['homepage_intro'] ?? '') ?: self::DEFAULT_SETTING['homepage_intro']
        ];

        $setting = $this->model->order('id', 'asc')->find();

        if ($setting) {
            $setting->save($payload);
        } else {
            $this->model->create($payload);
        }

        return $this->getSetting();
    }
}
