<?php

return [
    'limit' => [
        // 连续输错达到上限后，锁定登录。
        'max_attempts' => 10,
        'lockout_seconds' => 600,
    ],
    'captcha' => [
        // 总开关，关闭后前后端都不会启用验证码。
        'enabled' => true,
        // 显示模式：always 为前端默认展示，adaptive 为输错达到阈值后展示。
        'mode' => 'always',
        // adaptive 模式下，达到指定失败次数后开始要求验证码。
        'required_after_attempts' => 3,
        'ttl' => 120,
        'length' => 4,
        'width' => 130,
        'height' => 40,
    ],
];
