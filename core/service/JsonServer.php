<?php

namespace core\service;

use app\Request;
use core\service\crypto\TransportCryptoService;
use think\exception\HttpResponseException;
use think\Response;

class JsonServer
{
    public $json_success_code = 1;
    public $json_error_code = 0;

    /**
     * 杩斿洖灏佽鍚庣殑 API 鏁版嵁鍒板鎴风
     * @access protected
     * @param mixed  $data   瑕佽繑鍥炵殑鏁版嵁
     * @param int    $code   杩斿洖鐨刢ode 鎴愬姛1, 澶辫触0
     * @param mixed  $msg    鎻愮ず淇℃伅
     * @param int    $http_response_code  http鐘舵€佺爜
     * @return void
     * @throws HttpResponseException
     */
    public function result($data, $code, $msg = '', $http_response_code = 200)
    {
        $response = [
            'code' => $code,
            'msg' => $msg,
            'time' => time(),
            'data' => $data,
        ];

        $request = app()->request;
        if ($request instanceof Request && $request->isEncryptedRequest()) {
            try {
                $response = app()->make(TransportCryptoService::class)->encryptResponse($request, $response);
            } catch (\Throwable) {
                $http_response_code = 500;
                $response = [
                    'code' => 4699,
                    'msg' => 'response encryption failed',
                    'time' => time(),
                    'data' => [],
                ];
            }
        }

        $response = Response::create($response, 'json', $http_response_code);

        throw new HttpResponseException($response);
    }

    /**
     * 杩斿洖鎿嶄綔
     * @param array $data
     * @param int $http_status_code
     * @return void
     */
    public function response($http_status_code = 200, $msg = 'success', $data = [])
    {
        if ($http_status_code >= 200 && $http_status_code < 300) {
            $json_status_code = 1;
        } else {
            $json_status_code = 0;
        }

        return $this->result($data, $json_status_code, $msg, $http_status_code);
    }

    /**
     * 杩斿洖鎿嶄綔鎴愬姛json
     * @param array|string $data
     * @param string $msg
     * @return void
     */
    public function success($data = [], $msg = 'success')
    {
        if (is_string($data)) {
            $msg = $data;
            $data = [];
        }

        return $this->result($data, $this->json_success_code, $msg);
    }

    /**
     * 杩斿洖鎿嶄綔澶辫触json
     * @param string $msg
     * @param array $data
     * @return void
     */
    public function error($msg = 'error', $data = [])
    {
        return $this->result($data, $this->json_error_code, $msg);
    }
}
