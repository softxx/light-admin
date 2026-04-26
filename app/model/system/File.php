<?php

namespace app\model\system;

use app\model\system\search\FileSearch;
use core\base\BaseModel;

class File extends BaseModel
{
    use FileSearch;

    // 开启自动写入上传时间。
    protected $autoWriteTimestamp = true;

    // 文件表只记录创建时间，对应上传时间。
    protected $createTime = 'create_time';

    // 文件表没有 update_time 字段，关闭更新时间写入。
    protected $updateTime = false;

    // 读取时把时间戳格式化成后台列表可直接展示的时间。
    protected $type = [
        'create_time' => 'timestamp:Y/m/d H:i:s',
    ];

    /**
     * 统一 URL 分隔符，避免 Windows 路径反斜杠影响前端预览。
     *
     * @param mixed $value
     * @return string
     */
    public function getUrlAttr($value)
    {
        return str_replace('\\', '/', (string) $value);
    }

    /**
     * 上传用户关联，用于文件管理页展示上传人。
     *
     * @return \think\model\relation\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id')->bind(['realname', 'username']);
    }
}
