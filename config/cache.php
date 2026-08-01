<?php

declare(strict_types=1);

return [
    'driver' => env('CACHE_DRIVER', 'file'),
    'path' => STORAGE_PATH . '/cache/file-cache',
    'prefix' => 'bhitti:cache:'
];
