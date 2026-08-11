<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Driver
    |--------------------------------------------------------------------------
    | file: works on every normal single-server PHP installation.
    | redis: use for multiple servers or containers.
    | apcu/memcached: optional single-server stores.
    */
    'driver' => (string) env('RATE_LIMIT_STORE', 'file'),
    'prefix' => (string) env('RATE_LIMIT_PREFIX', 'bhitti:rate-limit:'),

    'redis' => [
        'connection' => (string) env('RATE_LIMIT_REDIS_CONNECTION', 'default'),
    ],

    'web' => [
        'guest' => [
            'max_attempts' => 120,
            'window_seconds' => 60,
        ],
        'authenticated' => [
            'max_attempts' => 240,
            'window_seconds' => 60,
        ],
    ],

    'api' => [
        'max_attempts' => 120,
        'window_seconds' => 60,
    ],

    'sensitive_routes' => [
        'max_attempts' => 20,
        'window_seconds' => 60,
        'routes' => [
            'POST /login',
            'POST /register',
            'POST /api/auth/login',
            'POST /api/auth/register',
            'POST /api/auth/forgot',
        ],
    ],

    'file' => [
        'path' => STORAGE_PATH . '/cache/rate-limit',
        'cleanup_probability' => 1,
        'stale_after_seconds' => 86400,
        'cleanup_delete_limit' => 100,
    ],
];
