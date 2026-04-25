<?php

namespace app\adminapi\validate\system;

use core\base\BaseValidate;

class SystemSettingValidate extends BaseValidate
{
    protected $rule = [
        'system_name' => 'require|max:100',
        'logo' => 'max:500',
        'favicon' => 'max:500',
        'homepage_enabled' => 'in:0,1'
    ];

    protected $message = [
        'system_name.require' => '系统名称不能为空',
        'system_name.max' => '系统名称不能超过100个字符',
        'logo.max' => 'Logo 地址不能超过500个字符',
        'favicon.max' => 'Favicon 地址不能超过500个字符',
        'homepage_enabled.in' => '首页开关参数错误'
    ];
}
