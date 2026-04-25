<?php

namespace app\model\system;

use app\model\system\search\LoginLogSearch;
use core\base\BaseModel;

class LoginLog extends BaseModel
{
    use LoginLogSearch;

    //开启自动写入时间戳
    protected $autoWriteTimestamp = true;

    //自动写入时间戳字段
    protected $createTime = 'login_time';

    // 关闭自动写入update_time字段
    protected $updateTime = false;

    //定义类型转换
    protected $type = [
        'login_time' => 'timestamp:Y/m/d H:i:s',
    ];

    //定义用户相对关联
    public function user()
    {
        return $this->belongsTo(User::class, 'account', 'username')->bind(['realname']);
    }
}
