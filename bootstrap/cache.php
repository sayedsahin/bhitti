<?php

declare(strict_types=1);

use Bhitti\Cache\Cache;
use Bhitti\Cache\CacheInterface;
use Bhitti\Cache\Drivers\ApcuCache;
use Bhitti\Cache\Drivers\ArrayCache;
use Bhitti\Cache\Drivers\FileCache;
use Bhitti\Cache\Drivers\MemcachedCache;
use Bhitti\Cache\Drivers\RedisCache;

$config = (array) config('cache');

Cache::setResolver(
    static function () use ($config): CacheInterface {
        $driver = $config['driver'];

        return match ($driver) {
            'array' => new ArrayCache(),

            'apcu' => new ApcuCache($config['prefix']),

            'file' => new FileCache($config['path']),

            'redis' => new RedisCache(config('database.redis', [])),

            'memcached' => new MemcachedCache(config('database.memcached'), $config['prefix']),

            default => throw new \RuntimeException(
                'Unsupported cache driver: ' . $driver
            ),
        };
    }
);