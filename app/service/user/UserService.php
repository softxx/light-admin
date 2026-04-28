<?php

namespace app\service\user;

use app\model\system\{AuthAccess, Menu, User};
use core\base\BaseService;
use core\exception\FailedException;

/**
 * 用户服务。
 *
 * 当前版本去掉部门、角色和数据权限，用户列表只维护账号基础信息；
 * 菜单和按钮权限通过 auth_access 直接挂到用户身上。
 */
class UserService extends BaseService
{
    public function __construct(User $model)
    {
        $this->model = $model;
    }

    /**
     * 获取用户列表。
     *
     * @return array
     */
    public function getList()
    {
        return $this->model
            ->search()
            ->withoutField('password')
            ->order('id', 'desc')
            ->paginate();
    }

    /**
     * 获取启用用户。
     *
     * @return array
     */
    public function getActiveUsers()
    {
        return $this->model
            ->search()
            ->where('status', 1)
            ->field('id,realname,avatar')
            ->cache(60)
            ->paginate();
    }

    /**
     * 获取当前用户信息。
     *
     * @return array
     */
    public function getUserInfo()
    {
        $id = request()->uid();
        $user = $this->model
            ->withoutField('password,create_time,status,pinyin')
            ->find($id);

        if (!$user) {
            throw new FailedException('用户不存在');
        }

        // 前端同时兼容 rules/buttons 字段，这里统一返回按钮权限标识。
        $user->rules = $this->getRules($user);
        $user->buttons = $user->rules;
        $user->is_super_admin = $this->isSuperAdmin($user);
        $user->avatar = $user->avatar ?: config('system.default_avatar');
        return $user;
    }

    /**
     * 获取用户按钮权限。
     */
    private function getRules(User $user): array
    {
        if ($this->isSuperAdmin($user)) {
            // 管理员不读取授权明细，直接拥有所有按钮权限。
            return Menu::where('type', 2)
                ->where('rules', '<>', '')
                ->sort('asc')
                ->column('rules');
        }

        // 普通用户只拥有 auth_access 中直接授予的按钮权限。
        $menuId = AuthAccess::getPermission($user->id);
        if (empty($menuId)) {
            return [];
        }

        return Menu::where('type', 2)
            ->whereIn('id', $menuId)
            ->where('rules', '<>', '')
            ->sort('asc')
            ->column('rules');
    }

    /**
     * 根据 id 获取用户。
     *
     * @param array $ids
     * @return array
     */
    public function getUserById(array $ids)
    {
        return $this->model->whereIn('id', $ids)->field('id,realname')->cache(60)->select();
    }

    /**
     * 保存用户。
     *
     * @param array $data
     * @return bool|int
     */
    public function save(array $data)
    {
        $data['password'] = config('system.def_password');
        return $this->model->storeBy($data);
    }

    /**
     * 获取编辑数据。
     *
     * @param int $id
     * @return array
     */
    public function edit($id)
    {
        return $this->model->withoutField(['password'])->findOrFail($id);
    }

    /**
     * 修改用户。
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update($id, array $data)
    {
        return $this->model->updateBy($id, $data);
    }

    /**
     * 修改状态。
     *
     * @param int $id
     * @return bool
     */
    public function changeStatus($id)
    {
        return $this->model->disOrEnable($id);
    }

    /**
     * 修改密码。
     *
     * @param array $data
     * @return mixed
     */
    public function changePassword($data)
    {
        return $this->model->updateBy($data['id'], ['password' => $data['password']]);
    }

    /**
     * 更新个人信息。
     *
     * @param int $id
     * @param array $data
     * @return mixed
     */
    public function updateInfo($id, $data)
    {
        return $this->model->updateBy($id, $data);
    }

    /**
     * 重置密码。
     *
     * @param int $id
     * @return array|bool
     */
    public function resetPassword($id)
    {
        $password = config('system.def_password');
        $result = $this->model->updateBy($id, ['password' => $password]);
        return $result ? ['password' => $password] : false;
    }

    /**
     * 删除用户。
     *
     * @param int $id
     * @return bool
     */
    public function delete($id)
    {
        $user = $this->model->find($id);
        if (!$user) {
            throw new FailedException('用户不存在');
        }

        if (
            $id == config('system.super_admin_id')
            || $user->is_admin
            || strtolower((string) $user->username) === 'admin'
        ) {
            throw new FailedException('管理员账号不允许删除');
        }

        try {
            $this->transaction(function () use ($id, $user) {
                // 删除用户时同步清理用户权限，避免 auth_access 残留孤儿数据。
                AuthAccess::where('user_id', $user->id)->delete();
                $this->model->deleteBy($id);
            });
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 管理员账号默认拥有全部权限。
     */
    private function isSuperAdmin(User $user): bool
    {
        return (int) ($user->is_admin ?? 0) === 1
            || (string) $user->id === (string) config('system.super_admin_id');
    }
}
