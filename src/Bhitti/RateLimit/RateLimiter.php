<?php

declare(strict_types=1);

namespace Bhitti\RateLimit;

use Bhitti\RateLimit\RateLimitDriver\ApcuDriver;
use Bhitti\RateLimit\RateLimitDriver\FileDriver;
use Bhitti\RateLimit\RateLimitDriver\MemcachedDriver;
use Bhitti\RateLimit\RateLimitDriver\RateLimitDriverInterface;
use Bhitti\RateLimit\RateLimitDriver\RedisDriver;
use InvalidArgumentException;
use RuntimeException;

final class RateLimiter
{
    private static ?RateLimitDriverInterface $driver = null;

    public static function hit(
        string $key,
        int $maxAttempts,
        int $windowSeconds
    ): RateLimitResult {
        self::validate($key, $maxAttempts, $windowSeconds);

        return self::driver()->hit(
            self::normalizeKey($key),
            $maxAttempts,
            $windowSeconds
        );
    }

    public static function clear(string $key): void
    {
        if (trim($key) === '') {
            throw new InvalidArgumentException(
                'Rate-limit key cannot be empty.'
            );
        }

        self::driver()->clear(self::normalizeKey($key));
    }

    public static function reset(): void
    {
        self::$driver = null;
    }

    private static function driver(): RateLimitDriverInterface
    {
        return self::$driver ??= self::resolve();
    }

    private static function resolve(): RateLimitDriverInterface
    {
        $driver = strtolower(trim((string) config(
            'rate_limit.driver',
            'file'
        )));

        return match ($driver) {
            'file' => new FileDriver(),
            'apcu' => new ApcuDriver(),
            'redis' => new RedisDriver(),
            'memcached' => new MemcachedDriver(),
            default => throw new RuntimeException(
                "Unsupported rate-limit driver: {$driver}"
            ),
        };
    }

    private static function normalizeKey(string $key): string
    {
        $prefix = (string) config(
            'rate_limit.prefix',
            'bhitti:rate-limit:'
        );

        return $prefix . hash('sha256', $key);
    }

    private static function validate(
        string $key,
        int $maxAttempts,
        int $windowSeconds
    ): void {
        if (trim($key) === '') {
            throw new InvalidArgumentException(
                'Rate-limit key cannot be empty.'
            );
        }

        if ($maxAttempts < 1) {
            throw new InvalidArgumentException(
                'Maximum attempts must be at least 1.'
            );
        }

        if ($windowSeconds < 1) {
            throw new InvalidArgumentException(
                'Rate-limit window must be at least 1 second.'
            );
        }
    }
}
