<?php

namespace app\adminapi\controller\system;

use app\adminapi\validate\system\SystemSettingValidate;
use app\service\system\SystemSettingService;
use core\base\BaseController;

class SystemSetting extends BaseController
{
    private $service;

    public function __construct(SystemSettingService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    /**
     * 后台获取系统设置
     */
    public function index()
    {
        $this->success($this->service->getSetting());
    }

    /**
     * 公开获取系统设置
     */
    public function publicInfo()
    {
        $this->success($this->service->getSetting());
    }

    /**
     * 保存系统设置
     */
    public function update(SystemSettingValidate $validate)
    {
        $data = $validate->validated();
        $result = $this->service->update($data);
        $this->success($result, '保存成功');
    }
}
