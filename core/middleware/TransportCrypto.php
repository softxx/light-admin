<?php

namespace core\middleware;

use app\Request;
use core\service\crypto\TransportCryptoService;

class TransportCrypto
{
    public function handle(Request $request, \Closure $next)
    {
        /** @var TransportCryptoService $service */
        $service = app()->make(TransportCryptoService::class);

        if ($service->shouldBypass($request)) {
            $request->markEncryptedRequest(false);
            return $next($request);
        }

        $decrypted = $service->decryptRequest($request);
        $request->replaceInputData($decrypted['data']);
        $request->markEncryptedRequest(true, $decrypted['context']);

        return $next($request);
    }
}
