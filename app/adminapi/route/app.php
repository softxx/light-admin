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

//鐧诲綍妯″潡
Route::group(function () {
    //璐﹀彿鐧诲綍
    Route::post('login', 'login.Index/login');
    //閫€鍑虹櫥褰?
    Route::post('logout', 'login.Index/logout');
    //鍒锋柊浠ょ墝
    Route::post('refreshToken', 'login.Index/refreshToken');
    //鍔犲瘑鍏挜鍏冧俊鎭?
    Route::get('crypto/meta', 'system.crypto/meta');
    Route::get('system_setting/public', 'system.system_setting/publicInfo');
})->middleware('transportCrypto');
