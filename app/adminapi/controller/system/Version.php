<?php

namespace app\adminapi\controller\system;

use app\service\system\VersionService;
use core\base\BaseController;

/**
 * 后台版本管理中心接口。
 */
class Version extends BaseController
{
    private VersionService $service;

    public function __construct(VersionService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    public function current()
    {
        $this->success($this->service->current());
    }

    /**
     * 从 GitHub Releases 中检查是否存在新版本。
     */
    public function check()
    {
        $this->success($this->service->check($this->request->param()));
    }

    /**
     * 下载并校验升级包。
     */
    public function download()
    {
        $this->success($this->service->download($this->request->param()), '升级包下载完成');
    }

    /**
     * 升级前环境预检查。
     */
    public function precheck()
    {
        $this->success($this->service->precheck($this->request->param()));
    }

    /**
     * 创建升级任务并拉起后台 CLI。
     */
    public function upgrade()
    {
        $this->success($this->service->startUpgrade($this->request->param()), '升级任务已启动');
    }

    /**
     * 基于最近一次成功升级的备份执行回滚。
     */
    public function rollback()
    {
        $this->success($this->service->startRollback($this->request->param()), '回滚任务已启动');
    }

    public function task()
    {
        $this->success($this->service->task((int) $this->request->param('id')));
    }

    public function tasks()
    {
        $this->success($this->service->tasks((int) $this->request->param('limit', 20)));
    }
}
