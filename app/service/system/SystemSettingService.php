<?php

namespace app\service\system;

use app\model\system\SystemSetting;
use core\base\BaseService;

class SystemSettingService extends BaseService
{
    private const DEFAULT_SETTING = [
        'system_name' => 'Art Design Pro',
        'logo' => '',
        'favicon' => ''
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
            array_pick('system_name,logo,favicon', $setting->toArray())
        );
    }

    public function update(array $data): array
    {
        $payload = [
            'system_name' => trim($data['system_name'] ?? '') ?: self::DEFAULT_SETTING['system_name'],
            'logo' => trim($data['logo'] ?? ''),
            'favicon' => trim($data['favicon'] ?? '')
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
