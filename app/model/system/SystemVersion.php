<?php

namespace app\model\system;

use core\base\BaseModel;

/**
 * 系统版本安装记录。
 */
class SystemVersion extends BaseModel
{
    protected $name = 'system_version';

    protected $autoWriteTimestamp = true;

    protected $createTime = 'create_time';

    protected $updateTime = false;
}
