<?php

namespace app\model\system\search;

use app\model\system\User;

trait OperateLogSearch
{
    use DynamicFilterSearchTrait;

    /**
     * Keep the original flat params for backward compatibility.
     */
    public function searchUserIdAttr($query, $value)
    {
        $query->where('user_id', $value);
    }

    public function searchMethodAttr($query, $value)
    {
        $query->where('method', $value);
    }

    public function searchCreateTimeAttr($query, $value)
    {
        $query->whereTime('create_time', 'between', between_time($value));
    }

    public function searchIpAttr($query, $value)
    {
        $query->whereLike('ip', $value);
    }

    protected function getDynamicFilterFieldConfigs(): array
    {
        return [
            'user_id' => [
                'type' => 'special_select',
                'handler' => 'applyUserFilterCondition',
            ],
            'method' => ['type' => 'select'],
            'module' => ['type' => 'text'],
            'operate' => ['type' => 'text'],
            'route' => ['type' => 'text'],
            'ip' => ['type' => 'text'],
            'params' => ['type' => 'text'],
            'create_time' => ['type' => 'date'],
        ];
    }

    /**
     * user_id supports both fuzzy lookup by realname and direct id matching.
     */
    protected function applyUserFilterCondition($query, array $filter): void
    {
        $field = $this->qualifyDynamicField($query, 'user_id');

        switch ($filter['operator']) {
            case 'contains':
                $matchedUserIds = $this->findUserIdsByKeyword((string) ($filter['value'] ?? ''));
                if (empty($matchedUserIds)) {
                    $query->whereRaw('1 = 0');
                    return;
                }

                $query->whereIn($field, $matchedUserIds);
                return;

            case 'not_contains':
                $matchedUserIds = $this->findUserIdsByKeyword((string) ($filter['value'] ?? ''));
                if (empty($matchedUserIds)) {
                    return;
                }

                $query->whereNotIn($field, $matchedUserIds);
                return;

            case 'eq':
            case 'neq':
                $this->applyDynamicFilterOnField(
                    $query,
                    $field,
                    $filter['operator'],
                    $filter['value'] ?? null,
                    'number'
                );
                return;

            case 'empty':
                $this->applyEmptyFilter($query, $field);
                return;

            case 'not_empty':
                $this->applyNotEmptyFilter($query, $field);
                return;
        }
    }

    protected function findUserIdsByKeyword(string $keyword): array
    {
        $keyword = trim($keyword);
        if ($keyword === '') {
            return [];
        }

        return User::whereLike('realname', $keyword)->column('id');
    }
}
