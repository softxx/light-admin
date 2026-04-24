<?php

namespace app\middleware;

use think\Response;

/**
 * 维护模式中间件。
 *
 * 升级任务执行期间会写入 runtime/maintenance.lock，普通业务请求返回维护提示，
 * 版本任务查询和加密元信息接口保持可用，方便前端持续轮询进度。
 */
class MaintenanceMode
{
    private array $allowedPrefixes = [
        'adminapi/version',
        'adminapi/crypto/meta',
    ];

    public function handle($request, \Closure $next)
    {
        $lockPath = app()->getRootPath() . 'runtime' . DIRECTORY_SEPARATOR . 'maintenance.lock';

        if (!is_file($lockPath) || $request->method() === 'OPTIONS') {
            return $next($request);
        }

        $path = trim(str_replace('\\', '/', $request->pathinfo()), '/');
        foreach ($this->allowedPrefixes as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return $next($request);
            }
        }

        $payload = json_decode((string) file_get_contents($lockPath), true);
        $message = is_array($payload) && !empty($payload['message'])
            ? (string) $payload['message']
            : '系统正在升级维护，请稍后再试';

        return Response::create([
            'code' => 503,
            'msg' => $message,
            'time' => time(),
            'data' => [
                'maintenance' => true,
                'task_id' => (int) ($payload['task_id'] ?? 0),
            ],
        ], 'json', 503);
    }
}
