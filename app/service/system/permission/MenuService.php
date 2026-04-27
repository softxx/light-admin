<?php

namespace app\service\system\permission;

use app\adminapi\validate\system\MenuValidate;
use app\model\system\{AuthAccess, Menu, User};
use core\base\BaseService;
use core\exception\FailedException;

class MenuService extends BaseService
{
    public function __construct(Menu $model)
    {
        $this->model = $model;
    }

    /**
     * 列表
     *
     * @return array
     */
    public function getList()
    {
        return $this->model
            ->field('*,id as value')
            ->append(['type_text', 'status'])
            ->sort('asc')
            ->select()
            ->toTree();
    }

    /**
     * 添加
     *
     * @param array $data
     * @return int
     */
    public function save($data)
    {
        $this->validate($data);
        $menuId = $this->model->storeBy($data);

        // 超级管理员默认拥有全部权限，新菜单自动补齐授权
        $authAccess = app()->make(AuthAccessService::class);
        $authAccess->create($menuId, config('system.super_admin_id'));

        return $menuId;
    }

    /**
     * 更新
     *
     * @param string|int $id
     * @param array $data
     * @return bool
     */
    public function update($id, $data)
    {
        $this->validate($data);
        return $this->model->updateBy($id, $data);
    }

    /**
     * 删除
     *
     * @param string|int $id
     * @return bool
     */
    public function delete($id)
    {
        $children = $this->model->where('pid', $id)->find();
        if ($children) {
            throw new FailedException('删除失败，存在子菜单无法删除');
        }

        try {
            $this->transaction(function () use ($id) {
                $this->model->deleteBy($id);
                $authAccess = app()->make(AuthAccessService::class);
                $authAccess->delete($id);
            });
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 获取路由
     *
     * @return array
     */
    public function getRouter()
    {
        $roleIds = User::find(request()->uid())->getRolesId();

        if ($this->isSuperAdmin($roleIds)) {
            return $this->model
                ->whereIn('type', [0, 1])
                ->order('sort', 'asc')
                ->select()
                ->toTree();
        }

        $menuIds = AuthAccess::getPermission($roleIds);
        return $this->model
            ->whereIn('id', $menuIds)
            ->whereIn('type', [0, 1])
            ->order('sort', 'asc')
            ->select()
            ->toTree();
    }

    /**
     * 获取全部菜单权限节点
     *
     * @return array
     */
    public static function getRuleAll()
    {
        return Menu::field('id,title,pid')
            ->where('type', '<>', 3)
            ->order('sort', 'asc')
            ->select()
            ->toTree();
    }

    /**
     * 参数校验
     *
     * @throws \think\ValidateException
     */
    public function validate($data)
    {
        if (!isset($data['type'])) {
            throw new FailedException('菜单类型不能为空');
        }

        if ($data['type'] == 2) {
            validate(MenuValidate::class)->scene('rules')->check($data);
            return;
        }

        if (!isset($data['open_type'])) {
            throw new FailedException('打开方式不能为空');
        }

        if ($data['open_type'] == 1) {
            validate(MenuValidate::class)->scene('linkUrl')->check($data);
            return;
        }

        if ($data['open_type'] == 2) {
            validate(MenuValidate::class)->scene('externalLink')->check($data);
            return;
        }

        validate(MenuValidate::class)->check($data);
    }

    /**
     * 是否包含超级管理员角色
     */
    private function isSuperAdmin(array $roleIds): bool
    {
        return in_array((int) config('system.super_admin_id'), array_map('intval', $roleIds), true);
    }
}
