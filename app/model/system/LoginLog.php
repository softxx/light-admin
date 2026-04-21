<?php

namespace app\model\system;

use app\model\system\search\LoginLogSearch;
use core\base\BaseModel;

class LoginLog extends BaseModel
{
    use LoginLogSearch;

    //寮€鍚嚜鍔ㄥ啓鍏ユ椂闂存埑
    protected $autoWriteTimestamp = true;

    //鑷姩鍐欏叆鏃堕棿鎴冲瓧娈?
    protected $createTime = 'login_time';

    // 鍏抽棴鑷姩鍐欏叆update_time瀛楁
    protected $updateTime = false;

    //瀹氫箟绫诲瀷杞崲
    protected $type = [
        'login_time' => 'timestamp:Y/m/d H:i:s',
    ];

    //瀹氫箟鐢ㄦ埛鐩稿鍏宠仈
    public function user()
    {
        return $this->belongsTo(User::class, 'account', 'username')->bind(['realname']);
    }
}
