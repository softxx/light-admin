<?php

namespace app\service\system\permission;

use app\adminapi\validate\system\MenuValidate;
use app\model\system\{AuthAccess, Menu, User};
use core\base\BaseService;
use core\exception\FailedException;

/**
 * 菜单服务。
 *
 * 菜单仍然是前后端权限的唯一节点来源；角色移除后，路由只按当前用户的
 * auth_access 授权结果过滤。
 */
class MenuService extends BaseService
{
    public function __construct(Menu $model)
    {
        $this->model = $model;
    }

    public function getList()
    {
        return $this->model
            ->field('*,id as value')
            ->append(['type_text', 'status'])
            ->sort('asc')
            ->select()
            ->toTree();
    }

    public function save($data)
    {
        $this->validate($data);
        $menuId = $this->model->storeBy($data);

        // 新增菜单默认授予系统管理员，避免管理员创建后自己看不到入口。
        $authAccess = app()->make(AuthAccessService::class);
        $authAccess->create($menuId, config('system.super_admin_id'));

        return $menuId;
    }

    public function update($id, $data)
    {
        $this->validate($data);
        return $this->model->updateBy($id, $data);
    }

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

    public function getRouter()
    {
        $user = User::find(request()->uid());
        if (!$user) {
            return [];
        }

        // 管理员拥有全量菜单；普通用户只返回直接授予的菜单节点。
        if ($this->isSuperAdmin($user)) {
            return $this->model
                ->whereIn('type', [0, 1])
                ->order('sort', 'asc')
                ->select()
                ->toTree();
        }

        $menuIds = AuthAccess::getPermission($user->id);
        return $this->model
            ->whereIn('id', $menuIds)
            ->whereIn('type', [0, 1])
            ->order('sort', 'asc')
            ->select()
            ->toTree();
    }

    public static function getRuleAll()
    {
        // 权限配置弹窗需要目录、菜单和按钮节点，外链类型不参与授权。
        return Menu::field('id,title,pid')
            ->where('type', '<>', 3)
            ->order('sort', 'asc')
            ->select()
            ->toTree();
    }

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

    private function isSuperAdmin(User $user): bool
    {
        return (int) ($user->is_admin ?? 0) === 1
            || (string) $user->id === (string) config('system.super_admin_id');
    }
}
