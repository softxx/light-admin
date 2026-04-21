<?php

namespace app\model\system\search;

use app\model\system\User;

trait LoginLogSearch
{
    use DynamicFilterSearchTrait;

    /**
     * Legacy flat filters remain available for existing callers.
     */
    public function searchAccountAttr($query, $value)
    {
        $query->whereLike('account', trim($value));
    }

    public function searchRealnameAttr($query, $value)
    {
        $matchedAccounts = $this->findAccountsByRealname($value);
        if (empty($matchedAccounts)) {
            $query->whereRaw('1 = 0');
            return;
        }

        $query->whereIn('account', $matchedAccounts);
    }

    public function searchLoginIpAttr($query, $value)
    {
        $query->whereLike('login_ip', trim($value));
    }

    public function searchLoginTimeAttr($query, $value)
    {
        $query->whereTime('login_time', 'between', between_time($value));
    }

    protected function getDynamicFilterFieldConfigs(): array
    {
        return [
            'account' => ['type' => 'text'],
            'realname' => [
                'type' => 'text',
                'handler' => 'applyRealnameFilterCondition',
            ],
            'login_ip' => ['type' => 'text'],
            'browser' => ['type' => 'text'],
            'os' => ['type' => 'text'],
            'login_time' => ['type' => 'date'],
        ];
    }

    /**
     * Login logs store account but not realname, so realname filters are
     * translated into matching accounts first.
     */
    protected function applyRealnameFilterCondition($query, array $filter): void
    {
        $field = $this->qualifyDynamicField($query, 'account');
        $operator = $filter['operator'] ?? '';

        if (in_array($operator, ['empty', 'not_empty'], true)) {
            $this->applyDynamicFilterOnField($query, $field, $operator, null, 'text');
            return;
        }

        $matchedAccounts = $this->findAccountsByRealname($filter['value'] ?? null);
        if (empty($matchedAccounts)) {
            if (in_array($operator, ['contains', 'eq'], true)) {
                $query->whereRaw('1 = 0');
            }

            return;
        }

        if (in_array($operator, ['contains', 'eq'], true)) {
            $query->whereIn($field, $matchedAccounts);
            return;
        }

        if (in_array($operator, ['not_contains', 'neq'], true)) {
            $query->whereNotIn($field, $matchedAccounts);
        }
    }

    protected function findAccountsByRealname($keyword): array
    {
        $keyword = is_string($keyword) ? trim($keyword) : '';
        if ($keyword === '') {
            return [];
        }

        return User::whereLike('realname|pinyin', $keyword)->column('username');
    }
}
