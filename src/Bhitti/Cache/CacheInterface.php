<?php
declare(strict_types=1);

namespace Bhitti\Cache;

interface CacheInterface
{
    public function get(string $key, mixed $default = null): mixed;

    public function put(string $key, mixed $value, int $ttl = 0): void;

    public function has(string $key): bool;

    public function forget(string $key): void;

    public function flush(): void;
}