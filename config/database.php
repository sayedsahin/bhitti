<?php

declare(strict_types=1);

return [
    'default' => env('DB_CONNECTION', 'mysql'),

    'connections' => [
        'mysql' => [
            'driver' => 'mysql',
            'host' => env('MYSQL_DB_HOST', env('DB_HOST', '127.0.0.1')),
            'port' => (int) env('MYSQL_DB_PORT', env('DB_PORT', 3306)),
            'database' => env('MYSQL_DB_NAME', env('DB_NAME', '')),
            'username' => env('MYSQL_DB_USERNAME', env('DB_USERNAME', 'root')),
            'password' => env('MYSQL_DB_PASSWORD', env('DB_PASSWORD', '')),
            'charset' => env('MYSQL_DB_CHARSET', 'utf8mb4'),
            'options' => [
                'persistent' => env('MYSQL_DB_PERSISTENT', env('DB_PERSISTENT', false)),
            ],
        ],

        'pgsql' => [
            'driver' => 'pgsql',
            'host' => env('PGSQL_DB_HOST', env('DB_HOST', '127.0.0.1')),
            'port' => (int) env('PGSQL_DB_PORT', env('DB_PORT', 5432)),
            'database' => env('PGSQL_DB_NAME', env('DB_NAME', '')),
            'username' => env('PGSQL_DB_USERNAME', env('DB_USERNAME', 'postgres')),
            'password' => env('PGSQL_DB_PASSWORD', env('DB_PASSWORD', '')),
            'options' => [
                'persistent' => env('PGSQL_DB_PERSISTENT', env('DB_PERSISTENT', false)),
            ],
        ],

        'sqlite' => [
            'driver' => 'sqlite',
            'database' => env('SQLITE_DATABASE', ROOT_PATH . '/database/database.sqlite'),
            'foreign_keys' => env('SQLITE_FOREIGN_KEYS', true),
            'busy_timeout' => (int) env('SQLITE_BUSY_TIMEOUT', 5000),
            'journal_mode' => env('SQLITE_JOURNAL_MODE', null),
            'synchronous' => env('SQLITE_SYNCHRONOUS', null),
        ],
    ],

    'redis' => [
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'port' => env('REDIS_PORT', 6379),
        'username' => env('REDIS_USERNAME', null),
        'password' => env('REDIS_PASSWORD', null),

        'db' => env('REDIS_DB', 0),
        'cache_db' => env('REDIS_CACHE_DB', 1),
        'rate_limit_db' => env('REDIS_RATE_LIMIT_DB', 2),
        'session_db' => env('REDIS_SESSION_DB', 3),

        'prefix' => env('CACHE_PREFIX', 'app_cache:'),
        'timeout' => env('REDIS_TIMEOUT', 2.0),
        'read_timeout' => env('REDIS_READ_TIMEOUT', 2.0),
    ],

    'memcached' => [
        'persistent_id' => env('MEMCACHED_PERSISTENT_ID', 'bhitti'),
        'connect_timeout' => env('MEMCACHED_CONNECT_TIMEOUT', 2000),

        'servers' => [
            [
                'host' => env('MEMCACHED_HOST', '127.0.0.1'),
                'port' => (int) env('MEMCACHED_PORT', 11211),
                'weight' => (int) env('MEMCACHED_WEIGHT', 0),
            ],
            // [
            //     'host' => env('MEMCACHED_HOST_2', '127.0.0.1'),
            //     'port' => (int) env('MEMCACHED_PORT_2', 11211),
            //     'weight' => (int) env('MEMCACHED_WEIGHT_2', 50),
            // ],
        ],
    ],
];
