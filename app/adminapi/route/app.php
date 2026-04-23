<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

use think\facade\Route;
use think\middleware\Throttle;

// 登录模块
Route::group(function () {
    // 账号登录
    Route::post('login', 'login.Index/login');
    // 读取验证码展示配置
    Route::get('login/captcha/meta', 'login.Index/captchaMeta');
    // 登录页初始化时获取验证码配置和默认验证码
    Route::post('login/captcha/bootstrap', 'login.Index/captchaBootstrap');
    // 刷新验证码图片
    Route::post('login/captcha', 'login.Index/captcha');
    // 退出登录
    Route::post('logout', 'login.Index/logout');
    // 刷新令牌
    Route::post('refreshToken', 'login.Index/refreshToken');
    // 加密公钥元信息
    Route::get('crypto/meta', 'system.crypto/meta');
    Route::get('system_setting/public', 'system.system_setting/publicInfo');
})->middleware('transportCrypto');
