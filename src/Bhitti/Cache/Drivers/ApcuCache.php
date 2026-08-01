<?php

declare(strict_types=1);

namespace Bhitti\Cache\Drivers;

use Bhitti\Cache\CacheInterface;
use InvalidArgumentException;
use RuntimeException;

final class ApcuCache implements CacheInterface
{
    private string $prefix;

    public function __construct(string $prefix = 'bhitti:cache:')
    {
        if (!function_exists('apcu_enabled') || !apcu_enabled()) {
            throw new RuntimeException('APCu is not available in the current environment.');
        }

        $prefix = trim($prefix);

        if ($prefix === '') {
            throw new InvalidArgumentException('APCu cache prefix cannot be empty.');
        }

        $this->prefix = rtrim($prefix, ':') . ':';
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $value = apcu_fetch($this->key($key), $success);

        return $success ? $value : $default;
    }

    public function put(string $key, mixed $value, int $ttl = 0): void
    {
        apcu_store($this->key($key), $value, $ttl);
    }

    public function has(string $key): bool
    {
        return apcu_exists($this->key($key));
    }

    public function forget(string $key): void
    {
        apcu_delete($this->key($key));
    }

    public function flush(): void
    {
        $pattern = '/^' . preg_quote($this->prefix, '/') . '/';
        $iterator = new \APCUIterator($pattern, \APC_ITER_KEY);

        apcu_delete($iterator);
    }

    private function key(string $key): string
    {
        return $this->prefix . $key;
    }
}