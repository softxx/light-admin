<?php

namespace app\model\system\search;

trait RoleSearch
{
    use DynamicFilterSearchTrait;

    public function searchKeyAttr($query, $value)
    {
        $query->whereLike('name|role_key', $value);
    }

    protected function getDynamicFilterFieldConfigs(): array
    {
        return [
            'name' => ['type' => 'text'],
            'role_key' => ['type' => 'text'],
            'note' => ['type' => 'text'],
            'data_range' => ['type' => 'select'],
            'create_time' => ['type' => 'date'],
        ];
    }
}
