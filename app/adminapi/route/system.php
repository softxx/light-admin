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

//绯荤粺妯″潡
Route::group(function () {
    //鑾峰彇鐢ㄦ埛淇℃伅
    Route::get('getUserInfo', 'system.user/getUserInfo');
    //鑾峰彇璺敱
    Route::get('getRouter', 'system.menu/getRouter');

    //鐧诲綍鏃ュ織
    Route::group('login_log', function () {
        //娓呯┖鐧诲綍鏃ュ織
        Route::delete('clear', 'system.login_log/clear');
        //鍒犻櫎鐧诲綍鏃ュ織
        Route::post('delete', 'system.login_log/delete');
        //瀵煎嚭鐧诲綍鏃ュ織
        Route::post('export', 'system.login_log/export');
    });

    //鎿嶄綔鏃ュ織
    Route::group('operate_log', function () {
        //娓呯┖鎿嶄綔鏃ュ織
        Route::delete('clear', 'system.operate_log/clear');
        //鍒犻櫎鎿嶄綔鏃ュ織
        Route::post('delete', 'system.operate_log/delete');
    });

    //瀛楀吀
    Route::group('dict', function () {
        //鏇存柊瀛楀吀缂撳瓨
        Route::post('updateCache', 'system.dict/updateCache');
        //鑾峰彇瀛楀吀
        Route::get('get', 'system.dict/get');
        //鏇存柊鎺掑簭
        Route::post('updateSort', 'system.dict/updateSort');
        //鏇存敼鐘舵€?
        Route::post('changeStatus/:id', 'system.dict/changeStatus');
    });

    //鐢ㄦ埛
    Route::group('user', function () {
        //鑾峰彇婵€娲荤殑鐢ㄦ埛
        Route::get('getActiveUsers', 'system.user/getActiveUsers');
        //淇敼鐢ㄦ埛鐘舵€?
        Route::put('changeStatus/:id', 'system.user/changeStatus');
        //閲嶇疆瀵嗙爜
        Route::put('resetPassword/:id', 'system.user/resetPassword');
        //淇敼瀵嗙爜
        Route::put('changePassword', 'system.user/changePassword');
        //鏍规嵁id鑾峰彇鐢ㄦ埛
        Route::get('getUserById', 'system.user/getUserById');
        //鏇存柊鐢ㄦ埛淇℃伅
        Route::put('updateInfo', 'system.user/updateInfo');
    });

    //瑙掕壊
    Route::group('role', function () {
        //鑾峰彇鍏ㄩ儴瑙掕壊
        Route::get('all', 'system.role/all');
    });

    //涓婁紶
    Route::group('upload', function () {
        //鏂囦欢涓婁紶
        Route::post('file', 'system.file/uploadFile');
        //鍥剧墖涓婁紶
        Route::post('image', 'system.file/uploadImg');
        //闄勪欢涓婁紶
        Route::post('attachment', 'system.file/uploadAttachment');
    });

    Route::group('system_setting', function () {
        Route::get('', 'system.system_setting/index');
        Route::post('', 'system.system_setting/update');
    });

    //璧勬簮璺敱
    Route::group(function () {
        //鐢ㄦ埛
        Route::resource('user', 'system.user');
        //鏉冮檺
        Route::resource('authAccess', 'system.authAccess')->only(['index', 'save']);
        //瑙掕壊
        Route::resource('role', 'system.role');
        //閮ㄩ棬
        Route::resource('department', 'system.department');
        //瀛楀吀
        Route::resource('dict', 'system.dict');
        //鑿滃崟
        Route::resource('menu', 'system.menu');
        //鎿嶄綔鏃ュ織
        Route::resource('operate_log', 'system.OperateLog');
        //鐧诲綍鏃ュ織
        Route::resource('login_log', 'system.LoginLog');
    });
})->middleware('auth');
