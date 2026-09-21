<?php

return [
    'paths' => [
        'api/*',
    ],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_filter(array_map('trim', explode(',', env(
        'CORS_ALLOWED_ORIGINS',
        'https://homespace.ge,https://www.homespace.ge,http://localhost:3000,http://127.0.0.1:3000'
    )))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,
];
