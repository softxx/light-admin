<?php

namespace app\model\system;

use core\base\BaseModel;

/**
 * 管理员权限关联模型。
 *
 * auth_access 现在保存管理员账号 user_id 和 menu_id 的直接映射，不再保存 role_id。
 */
class AuthAccess extends BaseModel
{
    public static function getPermission($userId)
    {
        return self::where('user_id', $userId)->column('menu_id');
    }
}
