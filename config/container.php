<?php
// config/container.php

return [

    'singletons' => [
        \Bhitti\Database\Database::class,
        // \Http\Request::class,
        // \Http\Response::class,
        // \Cache\FileCache::class,
    ],

    'bindings' => [
        // \Contracts\LoggerInterface::class =>
        //     \Systems\FileLogger::class,

        // \Contracts\CacheInterface::class =>
        //     \Cache\FileCache::class,
    ],

];
