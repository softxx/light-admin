<?php

namespace app\adminapi\controller\login;

use app\service\user\AuthService;
use app\service\user\LoginCaptchaService;
use core\base\BaseController;
use core\exception\FailedException;

class Index extends BaseController
{
    /**
     * 登录
     * @return \think\Response
     */
    public function login(AuthService $auth)
    {
        $username = $this->request->post('username');
        $password = $this->request->post('password');
        $captchaId = $this->request->post('captchaId', '');
        $captchaCode = $this->request->post('captchaCode', '');

        if (!$username || !$password) {
            throw new FailedException('用户名或密码不能为空');
        }

        $token = $auth->login($username, $password, $captchaId, $captchaCode);
        $this->success($token, '登录成功');
    }

    /**
     * 获取验证码显示配置
     * @return \think\Response
     */
    public function captchaMeta(LoginCaptchaService $captcha)
    {
        $this->success($captcha->meta());
    }

    /**
     * 登录页初始化读取验证码配置和默认验证码
     * @return \think\Response
     */
    public function captchaBootstrap(LoginCaptchaService $captcha)
    {
        $this->success($captcha->bootstrap());
    }

    /**
     * 获取新的验证码图片
     * @return \think\Response
     */
    public function captcha(LoginCaptchaService $captcha)
    {
        $this->success($captcha->issue());
    }

    /**
     * 退出登录
     * @return \think\Response
     */
    public function logout(AuthService $auth)
    {
        $refresToken = $this->request->post('refreshToken');
        if($refresToken) {
            $auth->logout($refresToken);
            $this->success('退出登录成功');
        }
        $this->error('退出登录失败');
    }

    /**
     * 刷新令牌
     * @return \think\Response
     */
    public function refreshToken(AuthService $auth)
    {
        try {
            $token = $auth->refreshToken();
        } catch (\Exception $e) {
           throw new FailedException($e->getMessage(), httpCode:401);
        }
        $this->success($token);
    }
}
