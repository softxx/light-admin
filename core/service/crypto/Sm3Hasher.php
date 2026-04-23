<?php

namespace core\service\crypto;

use Rtgm\sm\RtSm3;
use RuntimeException;

class Sm3Hasher
{
    private const BLOCK_SIZE = 64;

    public static function digestHex(string $message): string
    {
        return (new RtSm3())->digest($message, 1);
    }

    public static function hmacHex(string $message, string $key): string
    {
        if (strlen($key) > self::BLOCK_SIZE) {
            $key = self::digestRaw($key);
        }

        $key = str_pad($key, self::BLOCK_SIZE, "\0");
        $ipad = str_repeat(chr(0x36), self::BLOCK_SIZE);
        $opad = str_repeat(chr(0x5C), self::BLOCK_SIZE);

        $inner = self::digestRaw(($key ^ $ipad) . $message);

        return self::digestHex(($key ^ $opad) . $inner);
    }

    private static function digestRaw(string $message): string
    {
        $hex = self::digestHex($message);
        $raw = hex2bin($hex);
        if ($raw === false) {
            throw new RuntimeException('SM3 digest hex decode failed.');
        }

        return $raw;
    }
}
