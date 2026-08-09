<?php

declare(strict_types=1);

$redisDb = env('REDIS_DB');

return [
    'default' => (string) env('DB_CONNECTION', 'mysql'),

    'connections' => [
        'mysql' => [
            'driver' => 'mysql',
            'host' => (string) env('MYSQL_DB_HOST', env('DB_HOST', '127.0.0.1')),
            'port' => (int) env('MYSQL_DB_PORT', env('DB_PORT', 3306)),
            'database' => (string) env('MYSQL_DB_NAME', env('DB_NAME', '')),
            'username' => (string) env('MYSQL_DB_USERNAME', env('DB_USERNAME', 'root')),
            'password' => (string) env('MYSQL_DB_PASSWORD', env('DB_PASSWORD', '')),
            'charset' => (string) env('MYSQL_DB_CHARSET', 'utf8mb4'),
            'options' => [
                'persistent' => (bool) env('MYSQL_DB_PERSISTENT', env('DB_PERSISTENT', false)),
            ],
        ],

        'pgsql' => [
            'driver' => 'pgsql',
            'host' => (string) env('PGSQL_DB_HOST', env('DB_HOST', '127.0.0.1')),
            'port' => (int) env('PGSQL_DB_PORT', env('DB_PORT', 5432)),
            'database' => (string) env('PGSQL_DB_NAME', env('DB_NAME', '')),
            'username' => (string) env('PGSQL_DB_USERNAME', env('DB_USERNAME', 'postgres')),
            'password' => (string) env('PGSQL_DB_PASSWORD', env('DB_PASSWORD', '')),
            'options' => [
                'persistent' => (bool) env('PGSQL_DB_PERSISTENT', env('DB_PERSISTENT', false)),
            ],
        ],

        'sqlite' => [
            'driver' => 'sqlite',
            'database' => (string) env('SQLITE_DATABASE', ROOT_PATH . '/database/database.sqlite'),
            'foreign_keys' => (bool) env('SQLITE_FOREIGN_KEYS', true),
            'busy_timeout' => (int) env('SQLITE_BUSY_TIMEOUT', 5000),
            'journal_mode' => (string) env('SQLITE_JOURNAL_MODE', ''),
            'synchronous' => (string) env('SQLITE_SYNCHRONOUS', ''),
        ],
    ],

    'redis' => [
        'host' => (string) env('REDIS_HOST', '127.0.0.1'),
        'port' => (int) env('REDIS_PORT', 6379),
        'username' => env('REDIS_USERNAME', null),
        'password' => env('REDIS_PASSWORD', null),

        'db' => (int) ($redisDb ?? 0),
        'cache_db' => (int) env('REDIS_CACHE_DB',  $redisDb ?? 1),
        'rate_limit_db' => (int) env('REDIS_RATE_LIMIT_DB',  $redisDb ?? 2),
        'session_db' => (int) env('REDIS_SESSION_DB',  $redisDb ?? 3),

        'prefix' => (string) env('CACHE_PREFIX', 'bhitti:cache:'),
        'timeout' => (float) env('REDIS_TIMEOUT', 2.0),
        'read_timeout' => (float) env('REDIS_READ_TIMEOUT', 2.0),
    ],

    'memcached' => [
        'persistent_id' => (string) env('MEMCACHED_PERSISTENT_ID', 'bhitti'),
        'connect_timeout' => (int) env('MEMCACHED_CONNECT_TIMEOUT', 2000),

        'servers' => [
            [
                'host' => (string) env('MEMCACHED_HOST', '127.0.0.1'),
                'port' => (int) env('MEMCACHED_PORT', 11211),
                'weight' => (int) env('MEMCACHED_WEIGHT', 0),
            ],
            // [
            //     'host' => string) env('MEMCACHED_HOST_2', '127.0.0.1'),
            //     'port' => (int) env('MEMCACHED_PORT_2', 11211),
            //     'weight' => (int) env('MEMCACHED_WEIGHT_2', 50),
            // ],
        ],
    ],
];
