<?php

namespace core;

use think\facade\Db;

/**
 * 权限校验。
 */
/**
 * 角色移除后，接口权限直接通过 auth_access.user_id -> menu.rules 校验。
 */
class Permissions
{
    public $permissions_db = 'auth_access';

    /**
     * 检查用户是否拥有指定权限。
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
                    // 保留原有兼容逻辑：未登记到菜单表的规则不拦截。
                    return true;
                }
                $name = [$name];
            }
        }

        if ($this->isSuperAdmin($uid)) {
            return true;
        }

        // 用户权限直接关联菜单节点，不再经过角色或部门数据范围。
        $rules = Db::name($this->permissions_db)
            ->alias('a')
            ->join('menu b', 'a.menu_id = b.id')
            ->where('a.user_id', $uid)
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
     * 管理员账号默认拥有全部权限。
     */
    private function isSuperAdmin($uid): bool
    {
        if ((string) $uid === (string) config('system.super_admin_id')) {
            return true;
        }

        return (int) Db::name('user')->where('id', $uid)->value('is_admin') === 1;
    }
}
