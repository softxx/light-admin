<?php

namespace app\model\system;

use core\base\BaseModel;

/**
 * 版本升级任务记录。
 */
class UpgradeTask extends BaseModel
{
    protected $name = 'upgrade_task';

    protected $autoWriteTimestamp = true;

    protected $createTime = 'create_time';

    protected $updateTime = 'update_time';
}
