<?php

namespace app\model\system\search;

use app\model\system\User;

trait OperateLogSearch
{
    /**
     * 操作人
     */
    public function searchUserIdAttr($query, $value)
    {
        $query->where('user_id', $value);
    }

    /**
     * 请求方式
     */
    public function searchMethodAttr($query, $value)
    {
        $query->where('method', $value);
    }

    /**
     * 操作时间
     */
    public function searchCreateTimeAttr($query, $value)
    {
        $query->whereTime('create_time', 'between', between_time($value));
    }

    /**
     * IP 地址
     */
    public function searchIpAttr($query, $value)
    {
        $query->whereLike('ip', $value);
    }

    /**
     * 普通单条件过滤
     */
    public function searchQuickFilterAttr($query, $value)
    {
        $filter = $this->parseSingleDynamicFilter($value);
        if (empty($filter)) {
            return;
        }

        $this->applyDynamicFilter($query, $filter);
    }

    /**
     * 通用动态筛选
     */
    public function searchFiltersAttr($query, $value)
    {
        $groups = $this->parseDynamicFilterGroups($value);
        if (empty($groups)) {
            return;
        }

        $query->where(function ($groupQuery) use ($groups) {
            foreach ($groups as $index => $group) {
                $method = $index === 0 ? 'where' : 'whereOr';

                $groupQuery->{$method}(function ($conditionQuery) use ($group) {
                    foreach ($group['conditions'] as $filter) {
                        $this->applyDynamicFilter($conditionQuery, $filter);
                    }
                });
            }
        });
    }

    protected function getDynamicFilterFieldTypes(): array
    {
        return [
            'user_id' => 'special_select',
            'method' => 'select',
            'module' => 'text',
            'operate' => 'text',
            'route' => 'text',
            'ip' => 'text',
            'params' => 'text',
            'create_time' => 'date',
        ];
    }

    protected function getDynamicFilterOperators(): array
    {
        return [
            'text' => ['contains', 'not_contains', 'eq', 'neq', 'empty', 'not_empty'],
            'number' => ['eq', 'neq', 'gt', 'lt', 'gte', 'lte', 'empty', 'not_empty'],
            'date' => ['eq', 'gt', 'lt', 'gte', 'lte', 'empty', 'not_empty'],
            'select' => ['eq', 'neq', 'empty', 'not_empty'],
            'special_select' => ['contains', 'not_contains', 'eq', 'neq', 'empty', 'not_empty'],
        ];
    }

    protected function parseDynamicFilterGroups($value): array
    {
        if (is_string($value)) {
            $decoded = json_decode(html_entity_decode($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'), true);
            $value = json_last_error() === JSON_ERROR_NONE ? $decoded : [];
        }

        if (!is_array($value)) {
            return [];
        }

        if (isset($value[0]) && is_array($value[0]) && array_key_exists('field', $value[0])) {
            $value = [
                ['conditions' => $value],
            ];
        }

        $groups = [];

        foreach ($value as $group) {
            if (!is_array($group)) {
                continue;
            }

            $groupConditions = $group['conditions'] ?? [];
            if (!is_array($groupConditions)) {
                continue;
            }

            $conditions = $this->normalizeDynamicFilterConditions($groupConditions);
            if (empty($conditions)) {
                continue;
            }

            $groups[] = [
                'conditions' => $conditions,
            ];
        }

        return $groups;
    }

    protected function parseSingleDynamicFilter($value): ?array
    {
        if (is_string($value)) {
            $decoded = json_decode(html_entity_decode($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'), true);
            $value = json_last_error() === JSON_ERROR_NONE ? $decoded : [];
        }

        if (!is_array($value)) {
            return null;
        }

        return $this->normalizeDynamicFilterConditions([$value])[0] ?? null;
    }

    protected function normalizeDynamicFilterConditions(array $conditions): array
    {
        $fieldTypes = $this->getDynamicFilterFieldTypes();
        $operatorMap = $this->getDynamicFilterOperators();
        $filters = [];

        foreach ($conditions as $item) {
            if (!is_array($item)) {
                continue;
            }

            $field = $item['field'] ?? '';
            $operator = $item['operator'] ?? '';
            $fieldType = $fieldTypes[$field] ?? null;

            if (!$fieldType || !in_array($operator, $operatorMap[$fieldType] ?? [], true)) {
                continue;
            }

            $filter = [
                'field' => $field,
                'operator' => $operator,
                'value' => $item['value'] ?? null,
                'type' => $fieldType,
            ];

            if ($this->operatorRequiresValue($operator) && $this->isDynamicFilterValueEmpty($filter['value'])) {
                continue;
            }

            $filters[] = $filter;
        }

        return $filters;
    }

    protected function applyDynamicFilter($query, array $filter): void
    {
        if (($filter['field'] ?? '') === 'user_id') {
            $this->applyUserFilter($query, $filter['operator'], $filter['value'] ?? null);
            return;
        }

        $field = $this->qualifyDynamicField($query, $filter['field']);
        $value = $this->normalizeDynamicFilterValue($filter['type'], $filter['value'] ?? null);

        if ($this->operatorRequiresValue($filter['operator']) && $value === null) {
            return;
        }

        switch ($filter['operator']) {
            case 'contains':
                $query->where($field, 'like', '%' . $value . '%');
                break;

            case 'not_contains':
                $query->where($field, 'not like', '%' . $value . '%');
                break;

            case 'eq':
                $query->where($field, '=', $value);
                break;

            case 'neq':
                $query->where($field, '<>', $value);
                break;

            case 'gt':
                $query->where($field, '>', $value);
                break;

            case 'lt':
                $query->where($field, '<', $value);
                break;

            case 'gte':
                $query->where($field, '>=', $value);
                break;

            case 'lte':
                $query->where($field, '<=', $value);
                break;

            case 'empty':
                $this->applyEmptyFilter($query, $field, in_array($filter['type'], ['number', 'date'], true));
                break;

            case 'not_empty':
                $this->applyNotEmptyFilter($query, $field, in_array($filter['type'], ['number', 'date'], true));
                break;
        }
    }

    protected function applyUserFilter($query, string $operator, $value): void
    {
        $field = $this->qualifyDynamicField($query, 'user_id');

        switch ($operator) {
            case 'contains':
                $matchedUserIds = $this->findUserIdsByKeyword((string) $value);
                if (empty($matchedUserIds)) {
                    $query->whereRaw('1 = 0');
                    return;
                }
                $query->whereIn($field, $matchedUserIds);
                return;

            case 'not_contains':
                $matchedUserIds = $this->findUserIdsByKeyword((string) $value);
                if (empty($matchedUserIds)) {
                    return;
                }
                $query->whereNotIn($field, $matchedUserIds);
                return;

            case 'eq':
                $normalizedValue = $this->normalizeDynamicFilterValue('number', $value) ?? $value;
                $query->where($field, '=', $normalizedValue);
                return;

            case 'neq':
                $normalizedValue = $this->normalizeDynamicFilterValue('number', $value) ?? $value;
                $query->where($field, '<>', $normalizedValue);
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

    protected function normalizeDynamicFilterValue(string $type, $value)
    {
        if ($this->isDynamicFilterValueEmpty($value)) {
            return null;
        }

        return match ($type) {
            'number' => is_numeric($value) ? $value + 0 : null,
            'date' => $this->normalizeDateFilterValue($value),
            default => is_string($value) ? trim($value) : $value,
        };
    }

    protected function normalizeDateFilterValue($value): ?int
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        $timestamp = strtotime($value);
        return $timestamp === false ? null : $timestamp;
    }

    protected function operatorRequiresValue(string $operator): bool
    {
        return !in_array($operator, ['empty', 'not_empty'], true);
    }

    protected function isDynamicFilterValueEmpty($value): bool
    {
        if (is_array($value)) {
            foreach ($value as $item) {
                if (!$this->isDynamicFilterValueEmpty($item)) {
                    return false;
                }
            }
            return true;
        }

        return $value === null || (is_string($value) && trim($value) === '');
    }

    protected function qualifyDynamicField($query, string $field): string
    {
        return $query->getAlias() . '.' . $field;
    }

    protected function applyEmptyFilter($query, string $field, bool $treatZeroAsEmpty = false): void
    {
        $clauses = [
            "{$field} IS NULL",
            "{$field} = ''",
        ];

        if ($treatZeroAsEmpty) {
            $clauses[] = "{$field} = 0";
        }

        $query->whereRaw('(' . implode(' OR ', $clauses) . ')');
    }

    protected function applyNotEmptyFilter($query, string $field, bool $treatZeroAsEmpty = false): void
    {
        $clauses = [
            "{$field} IS NOT NULL",
            "{$field} <> ''",
        ];

        if ($treatZeroAsEmpty) {
            $clauses[] = "{$field} <> 0";
        }

        $query->whereRaw('(' . implode(' AND ', $clauses) . ')');
    }
}
