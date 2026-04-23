<?php

return [
    'enabled' => env('API_ENCRYPT_ENABLED', true),
    'protocol_version' => 1,
    'request_query_param' => '__enc',
    'timestamp_ttl' => 300,
    'nonce_ttl' => 300,
    'nonce_cache_prefix' => 'transport_crypto_nonce:',
    'key_dir' => 'crypto',
    'private_key_file' => 'transport_sm2_private.key',
    'public_key_file' => 'transport_sm2_public.key',
    'sm2' => [
        'mode' => 1,
        'asn1' => false,
    ],
    'sm4' => [
        'key_length' => 16,
        'iv_length' => 16,
    ],
    'sm3' => [
        'mode' => 'HMAC',
        'mac_hex_length' => 64,
    ],
    'whitelist' => [
        '/adminapi/crypto/meta',
        '/adminapi/system_setting/public',
        '/adminapi/upload/file',
        '/adminapi/upload/image',
        '/adminapi/upload/attachment',
        '/adminapi/login_log/export',
    ],
];
