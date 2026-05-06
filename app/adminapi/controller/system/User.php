<?php

namespace app\adminapi\controller\system;

use app\adminapi\validate\system\{PasswordValidate, UserValidate};
use app\service\user\UserService;
use core\base\BaseController;

class User extends BaseController
{
    private $service;

    public function __construct(UserService $service)
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
        $data = $this->service->getList();
        $this->success($data);
    }

    /**
     * 获取启用的管理员列表
     *
     * @return \think\Response
     */
    public function getActiveUsers()
    {
        $data = $this->service->getActiveUsers();
        $this->success($data);
    }

    /**
     * 新增
     *
     * @return \think\Response
     */
    public function save()
    {
        // 部门和角色已移除，权限可随新增管理员表单一并提交。
        $params = $this->request->param();
        $data = array_pick('username,phone,email,realname,avatar', $params);
        $menuIds = $this->getPermissionPayload($params);
        if ($menuIds !== null) {
            $data['menu_id'] = $menuIds;
        }

        validate(UserValidate::class)->scene('add')->check($data);
        $result = $this->service->save($data);
        $result ? $this->success('添加成功') : $this->error('添加失败');
    }

    /**
     * 获取编辑数据
     *
     * @param int $id
     * @return \think\Response
     */
    public function edit($id)
    {
        $data = $this->service->edit($id);
        $this->success($data);
    }

    /**
     * 更新
     *
     * @param int $id
     * @return \think\Response
     */
    public function update($id)
    {
        // 用户名只允许新增时设置；编辑时维护基础资料和管理员权限。
        $params = $this->request->param();
        $data = array_pick('id,email,realname,phone,avatar', $params);
        $menuIds = $this->getPermissionPayload($params);
        if ($menuIds !== null) {
            $data['menu_id'] = $menuIds;
        }

        validate(UserValidate::class)->scene('edit')->check($data);
        $result = $this->service->update($id, $data);
        $result ? $this->success('更新成功') : $this->error('更新失败');
    }

    /**
     * 更新个人信息
     *
     * @return \think\Response
     */
    public function updateInfo()
    {
        $id = request()->uid();
        $data = $this->request->param(['email', 'realname', 'phone', 'avatar']);
        $data['id'] = $id;
        validate(UserValidate::class)->scene('updateInfo')->check($data);
        $result = $this->service->updateInfo($id, $data);
        $result ? $this->success('更新成功') : $this->error('更新失败');
    }

    /**
     * 根据 id 获取管理员
     *
     * @return \think\Response
     */
    public function getUserById()
    {
        $ids = $this->request->param('id');
        if (!$ids) {
            $this->error('参数错误');
        }

        $data = $this->service->getUserById(explode(',', $ids));
        $this->success($data);
    }

    /**
     * 修改密码
     *
     * @return \think\Response
     */
    public function changePassword()
    {
        $data = $this->request->param();
        $data['id'] = request()->uid();
        validate(PasswordValidate::class)->check($data);
        $result = $this->service->changePassword($data);
        $result ? $this->success('修改成功') : $this->error('修改失败');
    }

    /**
     * 重置密码
     *
     * @param int $id
     * @return \think\Response
     */
    public function resetPassword($id)
    {
        validate(UserValidate::class)->scene('checkUser')->check(['id' => $id]);
        $result = $this->service->resetPassword($id);
        $result ? $this->success($result, '重置成功') : $this->error('重置失败');
    }

    /**
     * 修改状态
     *
     * @param int $id
     * @return \think\Response
     */
    public function changeStatus($id)
    {
        validate(UserValidate::class)->scene('checkUser')->check(['id' => $id]);
        $result = $this->service->changeStatus($id);
        $result ? $this->success('修改成功') : $this->error('修改失败');
    }

    /**
     * 删除
     *
     * @param int $id
     * @return \think\Response
     */
    public function delete($id)
    {
        validate(UserValidate::class)->scene('checkUser')->check(['id' => $id]);
        $result = $this->service->delete($id);
        $result ? $this->success('删除成功') : $this->error('删除失败');
    }

    /**
     * 获取用户信息
     *
     * @return \think\Response
     */
    public function getUserInfo()
    {
        $data = $this->service->getUserInfo();
        $this->success($data);
    }

    /**
     * 解析随管理员表单提交的权限节点。
     *
     * @param array $params
     * @return array|null
     */
    private function getPermissionPayload(array $params): ?array
    {
        if (!array_key_exists('menu_id', $params)) {
            return null;
        }

        if (!auth_check('system:authAccess:save')) {
            $this->error('无权限设置管理员权限');
        }

        return $this->request->param('menu_id/a', []);
    }
}
