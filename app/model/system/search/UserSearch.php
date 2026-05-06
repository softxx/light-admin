<?php

namespace app\model\system\search;

trait UserSearch
{
    use DynamicFilterSearchTrait;

    /**
     * Legacy flat filters remain available for the existing API contract.
     */
    public function searchKeyAttr($query, $value)
    {
        $query->whereLike('username|realname|pinyin', trim($value));
    }

    public function searchStatusAttr($query, $value)
    {
        $query->where('status', $value);
    }

    public function searchCreateTimeAttr($query, $value)
    {
        $query->whereTime('create_time', 'between', between_time($value));
    }

    protected function getDynamicFilterFieldConfigs(): array
    {
        // 部门/角色筛选已移除，管理员列表只保留账号基础字段过滤。
        return [
            'username' => ['type' => 'text'],
            'realname' => ['type' => 'text'],
            'phone' => ['type' => 'text'],
            'status' => ['type' => 'select'],
            'create_time' => ['type' => 'date'],
        ];
    }
}
