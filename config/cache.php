<?php

declare(strict_types=1);

return [
    'driver' => (string) env('CACHE_DRIVER', 'file'),
    'path' => STORAGE_PATH . '/cache/file-cache',
    'prefix' => (string) env('CACHE_PREFIX', 'bhitti:cache:'),

    'redis' => [
        'connection' => (string) env('CACHE_REDIS_CONNECTION', 'default'),
    ],
];
