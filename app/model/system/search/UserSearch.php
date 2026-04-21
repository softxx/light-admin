<?php

namespace app\model\system\search;

use app\model\system\{Department, Role};

trait UserSearch
{
    use DynamicFilterSearchTrait;

    /**
     * Legacy flat filters remain available for the existing API contract.
     */
    public function searchRolesAttr($query, $value)
    {
        $userIds = $this->findUserIdsByRole($value);
        $query->whereIn('id', $userIds);
    }

    public function searchKeyAttr($query, $value)
    {
        $query->whereLike('username|realname|pinyin', trim($value));
    }

    public function searchStatusAttr($query, $value)
    {
        $query->where('status', $value);
    }

    public function searchDeptidAttr($query, $value)
    {
        $query->whereIn('dept_id', Department::getChildrenDepartmentIds($value));
    }

    public function searchCreateTimeAttr($query, $value)
    {
        $query->whereTime('create_time', 'between', between_time($value));
    }

    protected function getDynamicFilterFieldConfigs(): array
    {
        return [
            'username' => ['type' => 'text'],
            'realname' => ['type' => 'text'],
            'phone' => ['type' => 'text'],
            'status' => ['type' => 'select'],
            'roles' => [
                'type' => 'select',
                'operators' => ['eq', 'neq'],
                'handler' => 'applyRoleFilterCondition',
            ],
            'dept_id' => [
                'type' => 'select',
                'operators' => ['eq', 'neq'],
                'handler' => 'applyDepartmentFilterCondition',
            ],
            'create_time' => ['type' => 'date'],
        ];
    }

    protected function applyRoleFilterCondition($query, array $filter): void
    {
        $matchedUserIds = $this->findUserIdsByRole($filter['value'] ?? null);
        if (empty($matchedUserIds)) {
            if (($filter['operator'] ?? '') === 'eq') {
                $query->whereRaw('1 = 0');
            }

            return;
        }

        $field = $this->qualifyDynamicField($query, 'id');
        $filter['operator'] === 'eq'
            ? $query->whereIn($field, $matchedUserIds)
            : $query->whereNotIn($field, $matchedUserIds);
    }

    protected function applyDepartmentFilterCondition($query, array $filter): void
    {
        $departmentIds = Department::getChildrenDepartmentIds($filter['value'] ?? 0);
        if (empty($departmentIds)) {
            if (($filter['operator'] ?? '') === 'eq') {
                $query->whereRaw('1 = 0');
            }

            return;
        }

        $field = $this->qualifyDynamicField($query, 'dept_id');
        $filter['operator'] === 'eq'
            ? $query->whereIn($field, $departmentIds)
            : $query->whereNotIn($field, $departmentIds);
    }

    protected function findUserIdsByRole($roleId): array
    {
        if ($roleId === null || $roleId === '') {
            return [];
        }

        $role = Role::find($roleId);
        if (!$role) {
            return [];
        }

        return $role->getUsers()->column('id');
    }
}
