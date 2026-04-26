<?php

namespace app\model\system\search;

use app\model\system\User;

trait FileSearch
{
    use DynamicFilterSearchTrait;

    /**
     * 按文件名模糊查询。
     */
    public function searchFilenameAttr($query, $value)
    {
        $query->whereLike('filename', trim((string) $value));
    }

    /**
     * 按文件扩展名精确查询。
     */
    public function searchFileExtAttr($query, $value)
    {
        $query->where('file_ext', trim((string) $value));
    }

    /**
     * 按 MIME 类型模糊查询。
     */
    public function searchMimeTypeAttr($query, $value)
    {
        $query->whereLike('mime_type', trim((string) $value));
    }

    /**
     * 按上传用户查询。
     */
    public function searchUserIdAttr($query, $value)
    {
        $query->where('user_id', $value);
    }

    /**
     * 按上传时间范围查询。
     */
    public function searchCreateTimeAttr($query, $value)
    {
        if (is_array($value)) {
            $query->whereTime('create_time', 'between', between_time($value));
        }
    }

    /**
     * 文件管理页支持的动态筛选字段。
     */
    protected function getDynamicFilterFieldConfigs(): array
    {
        return [
            'filename' => ['type' => 'text'],
            'file_ext' => ['type' => 'text'],
            'mime_type' => ['type' => 'text'],
            'file_size' => ['type' => 'number'],
            'user_id' => [
                'type' => 'special_select',
                'handler' => 'applyUserFilterCondition',
            ],
            'create_time' => ['type' => 'date'],
        ];
    }

    /**
     * 上传用户筛选。
     *
     * contains/not_contains 用姓名或账号模糊匹配用户；eq/neq 使用用户 ID。
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

    /**
     * 根据账号或姓名查找用户 ID。
     */
    protected function findUserIdsByKeyword(string $keyword): array
    {
        $keyword = trim($keyword);
        if ($keyword === '') {
            return [];
        }

        return User::where('username', 'like', '%' . $keyword . '%')
            ->whereOr('realname', 'like', '%' . $keyword . '%')
            ->column('id');
    }
}
