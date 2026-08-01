<?php

declare(strict_types=1);

namespace Bhitti\Cache\Drivers;

use Bhitti\Cache\CacheInterface;
use InvalidArgumentException;
use RuntimeException;

final class MemcachedCache implements CacheInterface
{
    private \Memcached $memcached;
    private string $prefix;
    private string $versionKey;
    private ?string $namespace = null;

    public function __construct(array $config, string $prefix = 'bhitti:cache:')
    {
        if (!class_exists(\Memcached::class)) {
            throw new RuntimeException('Memcached extension is not installed.');
        }

        $servers = (array) ($config['servers'] ?? []);

        if ($servers === []) {
            throw new InvalidArgumentException('At least one Memcached server is required.');
        }

        $prefix = trim($prefix);

        if ($prefix === '') {
            throw new InvalidArgumentException('Memcached cache prefix cannot be empty.');
        }

        if (strlen($prefix) > 100) {
            throw new InvalidArgumentException('Memcached cache prefix is too long.');
        }

        $this->prefix = rtrim($prefix, ':') . ':';
        $this->versionKey = $this->prefix . '__version';

        $persistentId = trim((string) ($config['persistent_id'] ?? ''));

        $this->memcached = $persistentId !== ''
            ? new \Memcached($persistentId)
            : new \Memcached();

        if ($this->memcached->getServerList() === []) {
            $this->memcached->setOption(\Memcached::OPT_BINARY_PROTOCOL, true);
            $this->memcached->setOption(
                \Memcached::OPT_CONNECT_TIMEOUT,
                (int) ($config['connect_timeout'] ?? 2000)
            );

            if (count($servers) > 1) {
                $this->memcached->setOption(\Memcached::OPT_LIBKETAMA_COMPATIBLE, true);
            }

            $this->addServers($servers);
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->memcached->get($this->key($key));

        return $this->memcached->getResultCode() === \Memcached::RES_SUCCESS
            ? $value
            : $default;
    }

    public function put(string $key, mixed $value, int $ttl = 0): void
    {
        $this->memcached->set($this->key($key), $value, $this->expiration($ttl));

    }

    public function has(string $key): bool
    {
        $this->memcached->get($this->key($key));

        return $this->memcached->getResultCode() === \Memcached::RES_SUCCESS;
    }

    public function forget(string $key): void
    {
        $this->memcached->delete($this->key($key));
    }

    public function flush(): void
    {
        $version = bin2hex(random_bytes(8));

        if ($this->memcached->set($this->versionKey, $version, 0)) {
            $this->namespace = $this->prefix . $version . ':';
        }
    }

    private function addServers(array $servers): void
    {
        $normalized = [];

        foreach ($servers as $server) {
            $host = (string) ($server['host'] ?? $server[0] ?? '');
            $port = (int) ($server['port'] ?? $server[1] ?? 11211);
            $weight = (int) ($server['weight'] ?? $server[2] ?? 0);

            if ($host === '' || $port < 1 || $port > 65535 || $weight < 0) {
                throw new InvalidArgumentException('Invalid Memcached server configuration.');
            }

            $normalized[] = [$host, $port, $weight];
        }

        if (!$this->memcached->addServers($normalized)) {
            throw new RuntimeException('Unable to configure Memcached servers.');
        }
    }

    private function key(string $key): string
    {
        return $this->namespace() . hash('sha256', $key);
    }

    private function namespace(): string
    {
        if ($this->namespace !== null) {
            return $this->namespace;
        }

        $version = $this->memcached->get($this->versionKey);

        if (
            $this->memcached->getResultCode() !== \Memcached::RES_SUCCESS
            || !is_string($version)
            || $version === ''
        ) {
            $version = '1';

            if (!$this->memcached->add($this->versionKey, $version, 0)) {
                $storedVersion = $this->memcached->get($this->versionKey);

                if (
                    $this->memcached->getResultCode() === \Memcached::RES_SUCCESS
                    && is_string($storedVersion)
                    && $storedVersion !== ''
                ) {
                    $version = $storedVersion;
                }
            }
        }

        return $this->namespace = $this->prefix . $version . ':';
    }

    private function expiration(int $ttl): int
    {
        if ($ttl === 0) {
            return 0;
        }

        return $ttl > 2592000 ? time() + $ttl : $ttl;
    }
}