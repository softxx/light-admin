<?php

namespace core;

use think\facade\Db;

/**
 * 权限校验
 */
class Permissions
{
    public $permissions_db = 'auth_access';
    public $user_role_db = 'user_role';

    /**
     * 检查权限
     *
     * @param string|array $name
     * @param int $uid
     * @param string $relation
     * @return bool
     */
    public function check($name, $uid, $relation = 'or')
    {
        if (empty($uid)) {
            return false;
        }

        if (is_string($name)) {
            $name = strtolower($name);
            if (strpos($name, ',') !== false) {
                $name = explode(',', $name);
            } else {
                $findAuthRuleCount = Db::name('menu')->where(['rules' => $name])->count();
                if ($findAuthRuleCount == 0) {
                    return true;
                }
                $name = [$name];
            }
        }

        $roles = $this->getRoles($uid);
        if (empty($roles)) {
            return false;
        }

        if ($this->isSuperAdmin($roles)) {
            return true;
        }

        $rules = Db::name($this->permissions_db)
            ->alias('a')
            ->join('menu b', 'a.menu_id = b.id')
            ->whereIn('a.role_id', $roles)
            ->whereIn('b.rules', $name)
            ->select();

        $list = [];
        foreach ($rules as $rule) {
            $list[] = strtolower($rule['rules']);
        }

        if ($relation === 'or' && !empty($list)) {
            return true;
        }

        $diff = array_diff($name, $list);
        if ($relation === 'and' && empty($diff)) {
            return true;
        }

        return false;
    }

    /**
     * 根据用户 id 获取角色
     *
     * @param int $uid
     * @return array
     */
    private function getRoles($uid)
    {
        return Db::name($this->user_role_db)->where('user_id', $uid)->column('role_id');
    }

    /**
     * 是否为超级管理员
     */
    private function isSuperAdmin(array $roles): bool
    {
        return in_array((int) config('system.super_admin_id'), array_map('intval', $roles), true);
    }
}
