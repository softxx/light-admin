<?php

namespace app\service\system\permission;

use app\model\system\Role;
use core\base\BaseService;
use core\exception\FailedException;

class RoleService extends BaseService
{
    public function __construct(Role $model)
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
        return $this->model->search()->paginate();
    }

    /**
     * 获取全部角色
     *
     * @return array
     */
    public function getAll()
    {
        return $this->model->field('id,name')->select();
    }

    /**
     * 保存
     *
     * @param array $data
     * @return int
     */
    public function save(array $data)
    {
        $id = $this->model->storeBy($data);
        if (($data['data_range'] ?? null) == Role::SELF_CHOOSE) {
            $this->model->saveDepartments($data['departments'] ?? []);
        }
        return $id;
    }

    /**
     * 更新
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update($id, array $data)
    {
        $this->assertManageable($id);
        $role = $this->findRoleOrFail($id);

        $result = $this->model->updateBy($id, $data);
        $role->updateDepartments($data['departments'] ?? []);
        return $result;
    }

    /**
     * 获取编辑数据
     *
     * @param int $id
     * @return array
     */
    public function edit($id)
    {
        $role = $this->findRoleOrFail($id);
        $role->departments = $role->getDepartmentId();
        return $role;
    }

    /**
     * 删除
     *
     * @param int|string $id
     * @return bool
     */
    public function delete($id)
    {
        $this->assertManageable($id, '删除');
        $role = $this->findRoleOrFail($id);

        if (!$role->getUsers()->isEmpty()) {
            throw new FailedException('删除失败，该角色已分配用户');
        }

        try {
            $this->transaction(function () use ($id, $role) {
                $this->model->deleteBy($id);
                $role->departments()->detach();
                $role->menus()->detach();
            });
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 当前角色是否为超级管理员角色
     */
    private function isSuperAdminRole($id): bool
    {
        return (string) $id === (string) config('system.super_admin_id');
    }

    /**
     * 超级管理员角色不允许维护
     */
    private function assertManageable($id, string $action = '维护'): void
    {
        if ($this->isSuperAdminRole($id)) {
            throw new FailedException("超级管理员角色默认拥有全部权限，不允许{$action}");
        }
    }

    /**
     * 查询角色，不存在时抛错
     */
    private function findRoleOrFail($id): Role
    {
        $role = $this->model->find($id);
        if (!$role) {
            throw new FailedException('角色不存在');
        }

        return $role;
    }
}
