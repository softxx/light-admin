<?php

namespace app\service\user;

use app\model\system\{AuthAccess, menu, User};
use core\base\BaseService;
use core\exception\FailedException;

class UserService extends BaseService
{
    public function __construct(User $model)
    {
        $this->model = $model;
    }

    /**
     * 获取列表
     *
     * @return array
     */
    public function getList()
    {
        return $this->model
            ->search()
            ->withoutField('password')
            ->order('id', 'desc')
            ->with(['roles', 'department'])
            ->paginate();
    }

    /**
     * 获取激活的用户
     *
     * @return array
     */
    public function getActiveUsers()
    {
        return $this->model
            ->search()
            ->where('status', 1)
            ->field('id,realname,dept_id,avatar')
            ->with(['department'])
            ->cache(60)
            ->paginate();
    }

    /**
     * 获取用户信息
     *
     * @return array
     */
    public function getUserInfo()
    {
        $id = request()->uid();
        $user = $this->model
            ->with(['department'])
            ->withoutField('password,create_time,status,pinyin')
            ->find($id);
        $role = $user->getRoles();
        $user->roles = $role->column('id');
        $user->role_name = $role->column('name');
        $user->rules = $this->getRules($role->column('id'));
        $user->avatar = $user->avatar ?: config('system.default_avatar');
        return $user;
    }

    /**
     * 获取角色权限
     *
     * @param array $roles
     * @return array
     */
    private function getRules(array $roles)
    {
        $roleIds = array_map('intval', $roles);

        if (in_array((int) config('system.super_admin_id'), $roleIds, true)) {
            return menu::where('type', 2)
                ->where('rules', '<>', '')
                ->sort('asc')
                ->column('rules');
        }

        $menuId = AuthAccess::getPermission($roleIds);
        return menu::where('type', 2)->whereIn('id', $menuId)->sort('asc')->column('rules');
    }

    /**
     * 根据 id 获取用户
     *
     * @param array $ids
     * @return array
     */
    public function getUserById(array $ids)
    {
        return $this->model->whereIn('id', $ids)->field('id,realname')->cache(60)->select();
    }

    /**
     * 保存
     *
     * @param array $data
     * @return bool
     */
    public function save(array $data)
    {
        $this->startTrans();
        try {
            $data['password'] = config('system.def_password');
            $this->model->storeBy($data);
            $this->model->saveRoles($data['roles']);
            $this->commit();
        } catch (\Exception $e) {
            $this->rollback();
            return false;
        }

        return true;
    }

    /**
     * 获取编辑数据
     *
     * @param int $id
     * @return array
     */
    public function edit($id)
    {
        $user = $this->model->withoutField(['password'])->findOrFail($id);
        $user->roles = $user->getRolesId();
        return $user;
    }

    /**
     * 修改
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update($id, array $data)
    {
        $this->startTrans();
        try {
            $this->model->updateBy($id, $data);
            $this->model->find($id)->updateRoles($data['roles']);
            $this->commit();
            return true;
        } catch (\Exception $e) {
            $this->rollback();
            return false;
        }
    }

    /**
     * 修改状态
     *
     * @param int $id
     * @return bool
     */
    public function changeStatus($id)
    {
        return $this->model->disOrEnable($id);
    }

    /**
     * 修改密码
     *
     * @param array $data
     * @return mixed
     */
    public function changePassword($data)
    {
        return $this->model->updateBy($data['id'], ['password' => $data['password']]);
    }

    /**
     * 更新个人信息
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
     * 重置密码
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
     * 删除用户
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
                $user->roles()->detach();
                $this->model->deleteBy($id);
            });
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
