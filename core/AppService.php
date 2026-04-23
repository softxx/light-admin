<?php

namespace core;

use EasyWeChat\MicroMerchant\Application as MicroMerchant;
use EasyWeChat\MiniProgram\Application as MiniProgram;
use EasyWeChat\OfficialAccount\Application as OfficialAccount;
use EasyWeChat\OpenPlatform\Application as OpenPlatform;
use EasyWeChat\OpenWork\Application as OpenWork;
use EasyWeChat\Payment\Application as Payment;
use EasyWeChat\Work\Application as Work;
use core\service\wechat\CacheBridge;
use think\Service;

class AppService extends Service
{
    public function register()
    {
        $this->registerQuery();
        $this->registerMiddleWareAlias();
        $this->registerWechat();
        $this->registerRequestMacro();
    }

    protected function registerRequestMacro()
    {
        $request = $this->app->request;
        if (!$request->hasMacro('uid')) {
            $request->macro('uid', fn() => 0);
        }
    }

    protected function registerQuery(): void
    {
        $connections = $this->app->config->get('database.connections');
        $connections['mysql']['query'] = BasisQuery::class;

        $this->app->config->set([
            'connections' => $connections,
        ], 'database');
    }

    protected function registerMiddleWareAlias()
    {
        $middleware = $this->app->config->get('middleware');
        $middleware['alias']['auth'] = [
            \core\middleware\TransportCrypto::class,
            \app\adminapi\middleware\JwtAuth::class,
            \app\adminapi\middleware\Permissions::class,
            \app\adminapi\middleware\RecordOperate::class,
        ];
        $middleware['alias']['transportCrypto'] = \core\middleware\TransportCrypto::class;
        $this->app->config->set($middleware, 'middleware');
    }

    protected function registerWechat()
    {
        $apps = [
            'official_account' => OfficialAccount::class,
            'work' => Work::class,
            'mini_program' => MiniProgram::class,
            'payment' => Payment::class,
            'open_platform' => OpenPlatform::class,
            'open_work' => OpenWork::class,
            'micro_merchant' => MicroMerchant::class,
        ];
        $wechatDefault = config('wechat.default') ? config('wechat.default') : [];

        foreach ($apps as $name => $app) {
            if (!config('wechat.' . $name)) {
                continue;
            }

            $configs = config('wechat.' . $name);
            foreach ($configs as $configName => $moduleDefault) {
                $this->app->bind('wechat.' . $name . '.' . $configName, function ($config = []) use ($app, $moduleDefault, $wechatDefault) {
                    $accountConfig = array_merge($moduleDefault, $wechatDefault, $config);
                    $accountApp = app($app, ['config' => $accountConfig]);

                    if (config('wechat.default.use_tp_cache')) {
                        $accountApp['cache'] = app(CacheBridge::class);
                    }

                    return $accountApp;
                });
            }

            if (isset($configs['default'])) {
                $this->app->bind('wechat.' . $name, 'wechat.' . $name . '.default');
            }
        }
    }
}
