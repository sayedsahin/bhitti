<?php
declare(strict_types=1);

namespace Bhitti\Cache\Drivers;

use Bhitti\Cache\CacheInterface;

final class ArrayCache implements CacheInterface
{
    private array $store = [];

    public function get(string $key, mixed $default = null): mixed
    {
        if (!isset($this->store[$key])) {
            return $default;
        }

        [$value, $expires] = $this->store[$key];

        if ($expires !== 0 && $expires < time()) {
            unset($this->store[$key]);
            return $default;
        }

        return $value;
    }

    public function put(string $key, mixed $value, int $ttl = 0): void
    {
        $expires = $ttl > 0 ? time() + $ttl : 0;

        $this->store[$key] = [$value, $expires];
    }

    public function has(string $key): bool
    {
        $missing = new \stdClass();

        return $this->get($key, $missing) !== $missing;
    }

    public function forget(string $key): void
    {
        unset($this->store[$key]);
    }

    public function flush(): void
    {
        $this->store = [];
    }
}