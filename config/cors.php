<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        env('FRONTEND_URL', 'https://matrimony.90skalyanam.com'),
        'https://matrimony.90skalyanam.com',
        'http://localhost:5173',
        'http://localhost:3000',
        'http://172.31.106.140:5173',
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,
];
