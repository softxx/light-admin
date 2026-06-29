<?php

namespace app\adminapi\controller\system;

use app\adminapi\validate\system\CacheValidate;
use app\service\system\CacheService;
use core\base\BaseController;

class Cache extends BaseController
{
    private CacheService $service;

    public function __construct(CacheService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    /**
     * 获取缓存概览
     */
    public function index()
    {
        $this->success($this->service->overview());
    }

    /**
     * 刷新字典缓存
     */
    public function refreshDict()
    {
        $this->success($this->service->refreshDictCache(), '字典缓存已刷新');
    }

    /**
     * 清理运行缓存
     */
    public function clearRuntime()
    {
        $this->success($this->service->clearRuntimeCache(), '运行缓存清理完成');
    }

    /**
     * 保存缓存驱动和 Redis 参数。
     *
     * 切换到 Redis 时服务层会先测试连接，避免错误配置影响后续缓存读写。
     */
    public function saveSetting(CacheValidate $validate)
    {
        $this->success($this->service->saveSetting($validate->validated()), '缓存配置已保存');
    }
}
