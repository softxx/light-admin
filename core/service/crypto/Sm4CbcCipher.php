<?php

namespace core\service\crypto;

use InvalidArgumentException;
use RuntimeException;

class Sm4CbcCipher
{
    private const BLOCK_SIZE = 16;

    public static function encrypt(string $plaintext, string $key, string $iv): string
    {
        self::assertKeyAndIv($key, $iv);

        $ciphertext = openssl_encrypt(
            $plaintext,
            'sm4-cbc',
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );

        if ($ciphertext === false) {
            throw new RuntimeException('SM4-CBC encryption failed.');
        }

        return $ciphertext;
    }

    public static function decrypt(string $ciphertext, string $key, string $iv): string
    {
        self::assertKeyAndIv($key, $iv);

        $plaintext = openssl_decrypt(
            $ciphertext,
            'sm4-cbc',
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );

        if ($plaintext === false) {
            throw new InvalidArgumentException('SM4-CBC decryption failed.');
        }

        return $plaintext;
    }

    private static function assertKeyAndIv(string $key, string $iv): void
    {
        if (strlen($key) !== self::BLOCK_SIZE) {
            throw new InvalidArgumentException('SM4 key length must be 16 bytes.');
        }

        if (strlen($iv) !== self::BLOCK_SIZE) {
            throw new InvalidArgumentException('SM4-CBC iv length must be 16 bytes.');
        }
    }
}
