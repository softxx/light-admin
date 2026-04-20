<?php

namespace app\service\system\permission;

use app\model\system\{AuthAccess, Menu, Role};
use core\base\BaseService;
use core\exception\FailedException;

class AuthAccessService extends BaseService
{
    /**
     * 获取角色权限
     *
     * @param string|int $roleId
     * @return array
     */
    public function getList($roleId)
    {
        $authNode = MenuService::getRuleAll();
        $selectedMenuIds = $this->isSuperAdminRole($roleId)
            ? $this->getAllMenuIds()
            : AuthAccess::getPermission($roleId);

        return [
            'authNode' => $authNode,
            'checked' => $this->getDisplayCheckedKeys($authNode, $selectedMenuIds)
        ];
    }

    /**
     * 保存角色权限
     *
     * @param string|int $roleId
     * @param array $menuIds
     * @return bool
     */
    public function save($roleId, array $menuIds): bool
    {
        if ($this->isSuperAdminRole($roleId)) {
            throw new FailedException('超级管理员角色默认拥有全部权限，不允许维护');
        }

        $role = Role::find($roleId);
        if (!$role) {
            throw new FailedException('角色不存在');
        }

        $menuIds = $this->expandMenuIdsWithAncestors($menuIds);

        try {
            $this->startTrans();
            $role->menus()->detach();

            if (!empty($menuIds)) {
                $role->menus()->attach($menuIds);
            }

            $this->commit();
            return true;
        } catch (\Exception $e) {
            $this->rollBack();
            return false;
        }
    }

    /**
     * 创建权限关联
     *
     * @param string|int $menuId
     * @param string|int $roleId
     * @return int
     */
    public function create($menuId, $roleId)
    {
        return AuthAccess::create(['menu_id' => $menuId, 'role_id' => $roleId]);
    }

    /**
     * 删除权限关联
     *
     * @param string|int $menuId
     * @return bool
     */
    public function delete($menuId)
    {
        return AuthAccess::where('menu_id', $menuId)->delete();
    }

    /**
     * 获取所有菜单 id
     */
    private function getAllMenuIds(): array
    {
        return array_map('intval', Menu::where('type', '<>', 3)->column('id'));
    }

    /**
     * 仅返回用于树形回显的最深层勾选节点，避免父节点回显时把整棵树勾满
     */
    private function getDisplayCheckedKeys(array $authNode, array $selectedMenuIds): array
    {
        $selectedMap = array_fill_keys($this->normalizeIds($selectedMenuIds), true);
        $checked = [];
        $this->collectDisplayCheckedKeys($authNode, $selectedMap, $checked);
        return array_values(array_unique($checked));
    }

    /**
     * 深度优先提取最深层已选节点
     */
    private function collectDisplayCheckedKeys(
        array $nodes,
        array $selectedMap,
        array &$checked
    ): bool {
        $hasSelectedNode = false;

        foreach ($nodes as $node) {
            $children = isset($node['children']) && is_array($node['children']) ? $node['children'] : [];
            $hasSelectedChildren = !empty($children)
                ? $this->collectDisplayCheckedKeys($children, $selectedMap, $checked)
                : false;
            $nodeId = isset($node['id']) ? (int) $node['id'] : 0;
            $isSelected = $nodeId > 0 && isset($selectedMap[$nodeId]);

            if ($isSelected && !$hasSelectedChildren) {
                $checked[] = $nodeId;
            }

            if ($isSelected || $hasSelectedChildren) {
                $hasSelectedNode = true;
            }
        }

        return $hasSelectedNode;
    }

    /**
     * 把叶子权限节点补齐到所有祖先节点，保证后端路由和按钮权限正常联动
     */
    private function expandMenuIdsWithAncestors(array $menuIds): array
    {
        $normalizedIds = $this->normalizeIds($menuIds);
        if (empty($normalizedIds)) {
            return [];
        }

        $menuParentMap = [];
        $menus = Menu::where('type', '<>', 3)->field('id,pid')->select();
        foreach ($menus as $menu) {
            $menuParentMap[(int) $menu['id']] = (int) $menu['pid'];
        }

        $expanded = [];
        foreach ($normalizedIds as $menuId) {
            $currentId = $menuId;
            while ($currentId > 0) {
                if (isset($expanded[$currentId])) {
                    break;
                }

                $expanded[$currentId] = $currentId;
                if (!array_key_exists($currentId, $menuParentMap)) {
                    break;
                }

                $currentId = (int) $menuParentMap[$currentId];
            }
        }

        return array_values($expanded);
    }

    /**
     * 规范化菜单 id
     */
    private function normalizeIds(array $menuIds): array
    {
        $normalized = [];
        foreach ($menuIds as $menuId) {
            if ($menuId === '' || $menuId === null) {
                continue;
            }

            $normalized[] = (int) $menuId;
        }

        return array_values(array_unique(array_filter($normalized)));
    }

    /**
     * 是否为超级管理员角色
     */
    private function isSuperAdminRole($roleId): bool
    {
        return (string) $roleId === (string) config('system.super_admin_id');
    }
}
