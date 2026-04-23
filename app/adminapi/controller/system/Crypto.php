<?php

namespace app\adminapi\controller\system;

use core\base\BaseController;
use core\service\crypto\TransportCryptoService;

class Crypto extends BaseController
{
    public function meta(TransportCryptoService $transportCrypto)
    {
        $this->success($transportCrypto->getMeta());
    }
}
