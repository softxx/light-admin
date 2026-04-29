<?php

namespace app\adminapi\controller\system;

use app\service\system\permission\AuthAccessService;
use core\base\BaseController;

/**
 * 用户权限控制器。
 *
 * auth_access 已从角色授权改为用户授权，这里的 id/user_id 都表示用户 ID。
 */
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
        // 获取指定用户的权限树和已勾选节点。
        $userId = $this->request->param('id');
        $data = $userId ? $this->service->getList($userId) : $this->service->getTree();
        $this->success($data);
    }

    /**
     * 保存权限
     *
     * @return \think\Response
     */
    public function save()
    {
        // 保存指定用户的菜单和按钮权限。
        $userId = $this->request->param('user_id');
        $menuIds = $this->request->param('menu_id/a', []);
        $result = $this->service->save($userId, $menuIds);
        $result ? $this->success('权限设置成功') : $this->error('权限设置失败');
    }
}
