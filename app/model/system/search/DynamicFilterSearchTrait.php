<?php

namespace app\model\system\search;

/**
 * Shared dynamic filter parser for list pages.
 *
 * Models can keep their existing flat search attrs for backward compatibility
 * and opt into quick_filter / filters by describing field metadata here.
 */
trait DynamicFilterSearchTrait
{
    /**
     * Apply a single quick filter payload.
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
     * Apply grouped advanced filters.
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

    /**
     * Field config example:
     * [
     *   'status' => ['type' => 'select'],
     *   'dept_id' => ['type' => 'select', 'handler' => 'applyDeptFilterCondition'],
     * ]
     */
    protected function getDynamicFilterFieldConfigs(): array
    {
        return [];
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
        $decodedValue = $this->decodeDynamicFilterValue($value);
        if (!is_array($decodedValue)) {
            return [];
        }

        if (isset($decodedValue[0]) && is_array($decodedValue[0]) && array_key_exists('field', $decodedValue[0])) {
            $decodedValue = [
                ['conditions' => $decodedValue],
            ];
        }

        $groups = [];

        foreach ($decodedValue as $group) {
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
        $decodedValue = $this->decodeDynamicFilterValue($value);
        if (!is_array($decodedValue)) {
            return null;
        }

        return $this->normalizeDynamicFilterConditions([$decodedValue])[0] ?? null;
    }

    protected function decodeDynamicFilterValue($value)
    {
        if (!is_string($value)) {
            return $value;
        }

        $decoded = json_decode(html_entity_decode($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'), true);
        return json_last_error() === JSON_ERROR_NONE ? $decoded : [];
    }

    protected function normalizeDynamicFilterConditions(array $conditions): array
    {
        $fieldConfigs = $this->getDynamicFilterFieldConfigs();
        $operatorMap = $this->getDynamicFilterOperators();
        $filters = [];

        foreach ($conditions as $item) {
            if (!is_array($item)) {
                continue;
            }

            $field = $item['field'] ?? '';
            $operator = $item['operator'] ?? '';
            $config = $fieldConfigs[$field] ?? null;
            $fieldType = $config['type'] ?? null;

            if (!$fieldType) {
                continue;
            }

            $allowedOperators = $config['operators'] ?? ($operatorMap[$fieldType] ?? []);
            if (!in_array($operator, $allowedOperators, true)) {
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
        $config = $this->getDynamicFilterFieldConfigs()[$filter['field'] ?? ''] ?? null;
        if (!$config) {
            return;
        }

        $handler = $config['handler'] ?? null;
        if ($handler && method_exists($this, $handler)) {
            $this->{$handler}($query, $filter, $config);
            return;
        }

        $queryField = $config['query_field'] ?? $filter['field'];
        $field = $this->qualifyDynamicField($query, $queryField);
        $this->applyDynamicFilterOnField(
            $query,
            $field,
            $filter['operator'],
            $filter['value'] ?? null,
            $filter['type']
        );
    }

    protected function applyDynamicFilterOnField($query, string $field, string $operator, $value, string $type): void
    {
        $normalizedValue = $this->normalizeDynamicFilterValue($type, $value);

        if ($this->operatorRequiresValue($operator) && $normalizedValue === null) {
            return;
        }

        switch ($operator) {
            case 'contains':
                $query->where($field, 'like', '%' . $normalizedValue . '%');
                break;

            case 'not_contains':
                $query->where($field, 'not like', '%' . $normalizedValue . '%');
                break;

            case 'eq':
                $query->where($field, '=', $normalizedValue);
                break;

            case 'neq':
                $query->where($field, '<>', $normalizedValue);
                break;

            case 'gt':
                $query->where($field, '>', $normalizedValue);
                break;

            case 'lt':
                $query->where($field, '<', $normalizedValue);
                break;

            case 'gte':
                $query->where($field, '>=', $normalizedValue);
                break;

            case 'lte':
                $query->where($field, '<=', $normalizedValue);
                break;

            case 'empty':
                $this->applyEmptyFilter($query, $field, in_array($type, ['number', 'date'], true));
                break;

            case 'not_empty':
                $this->applyNotEmptyFilter($query, $field, in_array($type, ['number', 'date'], true));
                break;
        }
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
        if (strpos($field, '.') !== false) {
            return $field;
        }

        $alias = method_exists($query, 'getAlias') ? $query->getAlias() : '';
        return $alias ? $alias . '.' . $field : $field;
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
