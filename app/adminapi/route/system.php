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

    Route::group('upload', function () {
        Route::post('file', 'system.file/uploadFile');
        Route::post('image', 'system.file/uploadImg');
        Route::post('attachment', 'system.file/uploadAttachment');
    });

    // 文件管理：只提供列表和删除，上传继续复用 upload 分组。
    Route::group('file', function () {
        Route::post('list', 'system.file/index');
        Route::post('delete', 'system.file/delete');
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
        // 管理员权限配置：权限不再挂角色，直接按管理员账号读取和保存菜单节点。
        Route::post('authAccess/index', 'system.authAccess/index');
        Route::post('dict/list', 'system.dict/index');
        Route::post('menu/list', 'system.menu/index');
        Route::post('login_log/list', 'system.LoginLog/index');
        Route::post('operate_log/list', 'system.OperateLog/index');

        // Department and role resources were removed; user is now the permission boundary.
        Route::resource('user', 'system.user')->except(['index', 'read', 'edit']);
        Route::resource('authAccess', 'system.authAccess')->only(['save']);
        Route::resource('dict', 'system.dict')->except(['index', 'read', 'edit']);
        Route::resource('menu', 'system.menu')->except(['index', 'read', 'edit']);
    });
})->middleware('auth');
