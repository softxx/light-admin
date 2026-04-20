<?php

namespace app\model\system;

use core\base\BaseModel;

class SystemSetting extends BaseModel
{
    protected $autoWriteTimestamp = true;

    protected $createTime = 'create_time';

    protected $updateTime = 'update_time';
}
