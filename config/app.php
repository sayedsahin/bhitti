<?php

declare(strict_types=1);

return [
    'name' => (string) env('APP_NAME', 'Bhitti Framework'),

    // false = production
    'debug' => (bool) env('APP_DEBUG', false),

    'url' => rtrim((string) env('BASE_URL', 'http://localhost'), '/'),
    'timezone' => (string) env('APP_TIMEZONE', 'UTC'),

    'trusted_proxies' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('TRUSTED_PROXIES', ''))
    ))),
];
