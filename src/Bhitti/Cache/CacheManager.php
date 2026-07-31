<?php

declare(strict_types=1);

namespace Bhitti\Cache;

use Bhitti\Cache\Drivers\ApcuCache;
use Bhitti\Cache\Drivers\ArrayCache;
use Bhitti\Cache\Drivers\FileCache;
use Bhitti\Cache\Drivers\MemcachedCache;
use Bhitti\Cache\Drivers\RedisCache;
use RuntimeException;

final class CacheManager
{
    public static function configure(array $config): void
    {
        Cache::setResolver(static function () use ($config): CacheInterface {
            $driver = $config['driver'] ?? 'file';

            return match ($driver) {
                'array' => new ArrayCache(),

                'apcu' => new ApcuCache(
                    (string) ($config['prefix'] ?? '')
                ),

                'file' => new FileCache(
                    (string) ($config['path'] ?? STORAGE_PATH . '/cache')
                ),

                'redis' => new RedisCache(
                    (array) config('database.redis', [])
                ),

                'memcached' => new MemcachedCache(
                    (array) config('database.memcached', []),
                    (string) ($config['prefix'] ?? '')
                ),

                default => throw new RuntimeException(
                    'Unsupported cache driver: ' . $driver
                ),
            };
        });
    }
}