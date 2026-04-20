<?php

namespace app\adminapi\controller\system;

use app\service\system\permission\AuthAccessService;
use core\base\BaseController;

class AuthAccess extends BaseController
{
    private $service;

    public function __construct(AuthAccessService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    /**
     * 列表
     *
     * @return \think\Response
     */
    public function index()
    {
        $roleId = $this->request->param('id');
        $data = $this->service->getList($roleId);
        $this->success($data);
    }

    /**
     * 保存权限
     *
     * @return \think\Response
     */
    public function save()
    {
        $roleId = $this->request->param('role_id');
        $menuIds = $this->request->param('menu_id/a', []);
        $result = $this->service->save($roleId, $menuIds);
        $result ? $this->success('权限设置成功') : $this->error('权限设置失败');
    }
}
