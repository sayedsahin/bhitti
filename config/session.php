<?php

declare(strict_types=1);

return [
    'driver' => env('SESSION_DRIVER', 'native'),

    'name' => env('SESSION_NAME', 'BHITTISESSID'),
    'lifetime' => (int) env('SESSION_LIFETIME', 7200),

    'path' => env('SESSION_PATH', '/'),
    'domain' => env('SESSION_DOMAIN', ''),
    'secure' => env('SESSION_SECURE', true),
    'httponly' => env('SESSION_HTTP_ONLY', true),
    'samesite' => env('SESSION_SAMESITE', 'Lax'),

    /*
    |--------------------------------------------------------------------------
    | Shared Remote Session Settings
    |--------------------------------------------------------------------------
    | Used by Redis and Memcached session drivers.
    */
    'prefix' => env('SESSION_PREFIX', 'bhitti:session:'),

    'lock' => env('SESSION_LOCK', true),
    'lock_ttl' => (int) env('SESSION_LOCK_TTL', 10),
    'lock_wait' => (float) env('SESSION_LOCK_WAIT', 2.0),
    'lock_sleep' => (int) env('SESSION_LOCK_SLEEP', 20000),
];
