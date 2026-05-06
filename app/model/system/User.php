<?php

namespace app\model\system;

use app\model\system\search\UserSearch;
use core\base\BaseModel;
use core\facade\Util;

/**
 * 管理员模型。
 *
 * 当前版本不再维护部门和角色关系，管理员只保留账号基础字段。
 */
class User extends BaseModel
{
    use UserSearch;

    protected $autoWriteTimestamp = true;

    protected $createTime = 'create_time';

    protected $updateTime = false;

    protected $readonly = ['username'];

    protected $type = [
        'create_time'  =>  'timestamp:Y/m/d'
    ];

    public function setPasswordAttr($value)
    {
        return password_hash($value, PASSWORD_DEFAULT);
    }

    public function setRealnameAttr($value, $data)
    {
        $this->set('pinyin', Util::toPinyin($data['realname']));
        return $value;
    }

    public function scopeSearchName($query, $name)
    {
        $query->whereLike('realname|pinyin', trim($name))->field('id,realname');
    }
}
