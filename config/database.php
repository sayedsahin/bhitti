<?php

declare(strict_types=1);

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

    /*
    |--------------------------------------------------------------------------
    | Redis Named Connections
    |--------------------------------------------------------------------------
    |
    | Performance-first default:
    | Cache, Session and RateLimit all map to the same "default" profile,
    | therefore one request-local Redis object / persistent FPM socket can be
    | reused by every Redis-backed framework service that is actually touched.
    |
    | Need isolation? Add another named profile here and point the service's
    | own config (cache.php/session.php/rate_limit.php) to that profile.
    |
    | A profile owns a fixed Redis endpoint + database. Framework services must
    | never issue SELECT themselves.
    |
    */
    'redis' => [
        'default' => 'default',

        'connections' => [
            'default' => [
                'host' => (string) env('REDIS_HOST', '127.0.0.1'),
                'port' => (int) env('REDIS_PORT', 6379),
                'username' => env('REDIS_USERNAME', null),
                'password' => env('REDIS_PASSWORD', null),
                'database' => (int) env('REDIS_DB', 0),

                // FPM-oriented fast path. Set false for non-persistent behavior.
                'persistent' => (bool) env('REDIS_PERSISTENT', true),
                'persistent_id' => env('REDIS_PERSISTENT_ID', null),

                'timeout' => (float) env('REDIS_TIMEOUT', 2.0),
                'read_timeout' => (float) env('REDIS_READ_TIMEOUT', 2.0),
                'tcp_keepalive' => (int) env('REDIS_TCP_KEEPALIVE', 0),
            ],

            /* ----------------------------------------------
            | OPTIONAL SPLIT PROFILE EXAMPLE
            ---------------------------------------------- */

            // 'session' => [
            //     'host' => (string) env('REDIS_SESSION_HOST', env('REDIS_HOST', '127.0.0.1')),
            //     'port' => (int) env('REDIS_SESSION_PORT', env('REDIS_PORT', 6379)),
            //     'username' => env('REDIS_SESSION_USERNAME', env('REDIS_USERNAME', null)),
            //     'password' => env('REDIS_SESSION_PASSWORD', env('REDIS_PASSWORD', null)),
            //     'database' => (int) env('REDIS_SESSION_DB', 0),
            //     'persistent' => (bool) env('REDIS_SESSION_PERSISTENT', true),
            //     'persistent_id' => env('REDIS_SESSION_PERSISTENT_ID', null),
            //     'timeout' => (float) env('REDIS_SESSION_TIMEOUT', env('REDIS_TIMEOUT', 2.0)),
            //     'read_timeout' => (float) env('REDIS_SESSION_READ_TIMEOUT', env('REDIS_READ_TIMEOUT', 2.0)),
            //     'tcp_keepalive' => (int) env('REDIS_SESSION_TCP_KEEPALIVE', env('REDIS_TCP_KEEPALIVE', 0)),
            // ],
        ],
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
        ],
    ],
];
