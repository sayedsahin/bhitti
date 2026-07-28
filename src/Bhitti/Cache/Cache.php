<?php

declare(strict_types=1);

namespace Bhitti\Cache;

use Closure;
use RuntimeException;

final class Cache
{
    private static ?CacheInterface $driver = null;

    private static ?Closure $resolver = null;

    /**
     * Direct driver registration.
     */
    public static function setDriver(CacheInterface $driver): void
    {
        self::$driver = $driver;
        self::$resolver = null;
    }

    /**
     * Lazy driver registration.
     *
     * The resolver will be executed during the first cache operation.
     */
    public static function setResolver(callable $resolver): void
    {
        self::$driver = null;

        self::$resolver = $resolver instanceof Closure
            ? $resolver
            : Closure::fromCallable($resolver);
    }

    private static function driver(): CacheInterface
    {
        if (self::$driver !== null) {
            return self::$driver;
        }

        if (self::$resolver === null) {
            throw new RuntimeException(
                'Cache driver is not configured.'
            );
        }

        $driver = (self::$resolver)();

        if (!$driver instanceof CacheInterface) {
            throw new RuntimeException(
                'Cache resolver must return CacheInterface.'
            );
        }

        /*
         * After the first resolve, the driver will cache directly.
         * The next Cache call will not have a closure call.
         */
        self::$driver = $driver;
        self::$resolver = null;

        return $driver;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return self::driver()->get($key, $default);
    }

    public static function put(string $key, mixed $value, int $ttl = 0): void
    {
        if ($ttl < 0) {
            throw new \InvalidArgumentException('Cache TTL cannot be negative.');
        }
        self::driver()->put($key, $value, $ttl);
    }

    public static function has(string $key): bool
    {
        return self::driver()->has($key);
    }

    public static function forget(string $key): void
    {
        self::driver()->forget($key);
    }

    public static function flush(): void
    {
        self::driver()->flush();
    }

    public static function remember(string $key, int $ttl, callable $callback): mixed
    {
        if ($ttl < 0) {
            throw new \InvalidArgumentException('Cache TTL cannot be negative.');
        }
        /*
         * driver() has been called once.
         */
        $driver = self::driver();

        $missing = new \stdClass();

        $value = $driver->get($key,  $missing);

        if ($value !== $missing) {
            return $value;
        }

        $value = $callback();

        $driver->put($key, $value, $ttl);

        return $value;
    }
}