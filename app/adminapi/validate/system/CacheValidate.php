<?php

namespace app\adminapi\validate\system;

use core\base\BaseValidate;

class CacheValidate extends BaseValidate
{
    protected $rule = [
        'driver' => 'require|in:file,redis',
    ];

    protected $message = [
        'driver.require' => '缓存驱动不能为空',
        'driver.in' => '缓存驱动仅支持 file 或 redis',
    ];
}
