<?php

namespace app\adminapi\validate\system;

use app\model\system\User;
use core\base\BaseValidate;

class UserValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require|checkAdmin',
        'username' => 'require|max:30|unique:user|alphaNum',
        'roles' => 'require|array',
        'realname' => 'require|max:10',
        'dept_id' => 'require',
        'phone' => 'mobile|unique:user',
        'email' => 'email|unique:user'
    ];

    public $field = [
        'realname' => '姓名',
        'roles' => '角色',
        'username' => '用户名',
        'dept_id' => '部门',
        'email' => '邮箱',
        'phone' => '手机号'
    ];

    public function sceneAdd()
    {
        return $this->remove('id', ['require', 'checkAdmin']);
    }

    public function sceneEdit()
    {
        return $this->only(['id', 'realname', 'roles', 'dept_id', 'phone', 'email']);
    }

    public function sceneUpdateInfo()
    {
        return $this->only(['realname', 'phone', 'email']);
    }

    public function sceneCheckUser()
    {
        return $this->only(['id']);
    }

    /**
     * 校验用户是否允许操作
     */
    public function checkAdmin($value)
    {
        $user = User::find($value);
        if (!$user) {
            return '用户不存在';
        }

        if ($user->is_admin) {
            return '管理员账号不允许操作';
        }

        return true;
    }
}
