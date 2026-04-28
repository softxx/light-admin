<?php

namespace app\service\system\permission;

use app\model\system\{AuthAccess, Menu, User};
use core\base\BaseService;
use core\exception\FailedException;

/**
 * 用户权限服务。
 *
 * 部门和角色移除后，auth_access 表直接维护 user_id 与 menu_id 的关系。
 * 这里集中处理权限树回显、用户授权保存，以及新增菜单时给管理员补授权。
 */
class AuthAccessService extends BaseService
{
    /**
     * 获取用户权限树和已勾选节点。
     *
     * @param string|int $userId
     * @return array
     */
    public function getList($userId)
    {
        $user = $this->findUserOrFail($userId);
        $authNode = MenuService::getRuleAll();
        // 管理员不依赖 auth_access 明细，始终按全量权限回显。
        $selectedMenuIds = $this->isSuperAdminUser($user)
            ? $this->getAllMenuIds()
            : AuthAccess::getPermission($user->id);

        return [
            'authNode' => $authNode,
            'checked' => $this->getDisplayCheckedKeys($authNode, $selectedMenuIds)
        ];
    }

    /**
     * 保存用户权限。
     *
     * @param string|int $userId
     * @param array $menuIds
     * @return bool
     */
    public function save($userId, array $menuIds): bool
    {
        $user = $this->findUserOrFail($userId);
        if ($this->isSuperAdminUser($user)) {
            throw new FailedException('管理员账号默认拥有全部权限，不允许维护');
        }

        // 前端只提交勾选节点；后端补齐父级目录，保证路由树能正常展示。
        $menuIds = $this->expandMenuIdsWithAncestors($menuIds);

        try {
            $this->startTrans();
            // 直接按用户重建授权，避免保留已经取消勾选的旧权限。
            AuthAccess::where('user_id', $user->id)->delete();

            if (!empty($menuIds)) {
                $rows = array_map(fn($menuId) => [
                    'user_id' => $user->id,
                    'menu_id' => $menuId,
                ], $menuIds);
                AuthAccess::insertAll($rows);
            }

            $this->commit();
            return true;
        } catch (\Exception $e) {
            $this->rollBack();
            return false;
        }
    }

    /**
     * 创建权限关联。
     *
     * @param string|int $menuId
     * @param string|int $userId
     * @return int
     */
    public function create($menuId, $userId)
    {
        return AuthAccess::create(['menu_id' => $menuId, 'user_id' => $userId]);
    }

    /**
     * 删除某个菜单的权限关联。
     *
     * @param string|int $menuId
     * @return bool
     */
    public function delete($menuId)
    {
        return AuthAccess::where('menu_id', $menuId)->delete();
    }

    /**
     * 获取所有菜单和按钮节点。
     */
    private function getAllMenuIds(): array
    {
        return array_map('intval', Menu::where('type', '<>', 3)->column('id'));
    }

    /**
     * 仅返回用于树形回显的最深层勾选节点，避免父节点回显时把整棵树勾满。
     */
    private function getDisplayCheckedKeys(array $authNode, array $selectedMenuIds): array
    {
        // Element Plus 树组件回显父节点会级联全选子节点，所以只返回最深层节点。
        $selectedMap = array_fill_keys($this->normalizeIds($selectedMenuIds), true);
        $checked = [];
        $this->collectDisplayCheckedKeys($authNode, $selectedMap, $checked);
        return array_values(array_unique($checked));
    }

    /**
     * 深度优先提取最深层已选节点。
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
     * 把叶子权限节点补齐到所有祖先节点，保证后端路由和按钮权限正常联动。
     */
    private function expandMenuIdsWithAncestors(array $menuIds): array
    {
        $normalizedIds = $this->normalizeIds($menuIds);
        if (empty($normalizedIds)) {
            return [];
        }

        // 先构建 id => pid 映射，避免每个节点向上查找时重复访问数据库。
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
     * 规范化菜单 id。
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
     * 查询用户，不存在时抛错。
     */
    private function findUserOrFail($userId): User
    {
        $user = User::find($userId);
        if (!$user) {
            throw new FailedException('用户不存在');
        }

        return $user;
    }

    /**
     * 管理员账号默认拥有全部权限。
     */
    private function isSuperAdminUser(User $user): bool
    {
        return (int) ($user->is_admin ?? 0) === 1
            || (string) $user->id === (string) config('system.super_admin_id');
    }
}
