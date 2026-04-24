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

Route::group(function () {
    Route::post('getUserInfo', 'system.user/getUserInfo');
    Route::post('getRouter', 'system.menu/getRouter');

    Route::group('login_log', function () {
        Route::delete('clear', 'system.login_log/clear');
        Route::post('delete', 'system.login_log/delete');
        Route::post('export', 'system.login_log/export');
    });

    Route::group('operate_log', function () {
        Route::delete('clear', 'system.operate_log/clear');
        Route::post('delete', 'system.operate_log/delete');
    });

    Route::group('dict', function () {
        Route::post('updateCache', 'system.dict/updateCache');
        Route::post('get', 'system.dict/get');
        Route::post('updateSort', 'system.dict/updateSort');
        Route::post('changeStatus/:id', 'system.dict/changeStatus');
    });

    Route::group('user', function () {
        Route::post('getActiveUsers', 'system.user/getActiveUsers');
        Route::put('changeStatus/:id', 'system.user/changeStatus');
        Route::put('resetPassword/:id', 'system.user/resetPassword');
        Route::put('changePassword', 'system.user/changePassword');
        Route::post('getUserById', 'system.user/getUserById');
        Route::put('updateInfo', 'system.user/updateInfo');
    });

    Route::group('role', function () {
        Route::post('all', 'system.role/all');
    });

    Route::group('upload', function () {
        Route::post('file', 'system.file/uploadFile');
        Route::post('image', 'system.file/uploadImg');
        Route::post('attachment', 'system.file/uploadAttachment');
    });

    Route::group('system_setting', function () {
        Route::post('query', 'system.system_setting/index');
        Route::post('', 'system.system_setting/update');
    });

    Route::group('cache', function () {
        Route::post('overview', 'system.cache/index');
        Route::post('refreshDict', 'system.cache/refreshDict');
        Route::post('clearRuntime', 'system.cache/clearRuntime');
    });

    Route::group('version', function () {
        Route::post('current', 'system.version/current');
        Route::post('check', 'system.version/check');
        Route::post('download', 'system.version/download');
        Route::post('precheck', 'system.version/precheck');
        Route::post('upgrade', 'system.version/upgrade');
        Route::post('rollback', 'system.version/rollback');
        Route::post('task', 'system.version/task');
        Route::post('tasks', 'system.version/tasks');
    });

    Route::group(function () {
        Route::post('user/list', 'system.user/index');
        Route::post('user/:id/edit', 'system.user/edit');
        Route::post('authAccess/index', 'system.authAccess/index');
        Route::post('role/list', 'system.role/index');
        Route::post('role/:id/edit', 'system.role/edit');
        Route::post('department/list', 'system.department/index');
        Route::post('department/:id/edit', 'system.department/edit');
        Route::post('dict/list', 'system.dict/index');
        Route::post('menu/list', 'system.menu/index');
        Route::post('login_log/list', 'system.LoginLog/index');
        Route::post('operate_log/list', 'system.OperateLog/index');

        Route::resource('user', 'system.user')->except(['index', 'read', 'edit']);
        Route::resource('authAccess', 'system.authAccess')->only(['save']);
        Route::resource('role', 'system.role')->except(['index', 'read', 'edit']);
        Route::resource('department', 'system.department')->except(['index', 'read', 'edit']);
        Route::resource('dict', 'system.dict')->except(['index', 'read', 'edit']);
        Route::resource('menu', 'system.menu')->except(['index', 'read', 'edit']);
    });
})->middleware('auth');
