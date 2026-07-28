<?php

declare(strict_types=1);

namespace Bhitti\RateLimit\RateLimitDriver;

use Bhitti\RateLimit\RateLimitResult;
use Memcached;
use RuntimeException;

final class MemcachedDriver implements RateLimitDriverInterface
{
    private Memcached $memcached;

    public function __construct()
    {
        if (!class_exists(Memcached::class)) {
            throw new RuntimeException('PHP Memcached extension is not installed.');
        }

        $config = (array) config('database.memcached', []);
        $servers = (array) ($config['servers'] ?? []);

        if ($servers === []) {
            throw new RuntimeException('No Memcached server is configured.');
        }

        $persistentId = trim((string) ($config['persistent_id'] ?? ''));

        $this->memcached = $persistentId !== ''
            ? new Memcached($persistentId)
            : new Memcached();

        $this->memcached->setOption(Memcached::OPT_BINARY_PROTOCOL, true);
        $this->memcached->setOption(
            Memcached::OPT_CONNECT_TIMEOUT,
            (int) ($config['connect_timeout'] ?? 2000)
        );

        if (count($servers) > 1) {
            $this->memcached->setOption(Memcached::OPT_LIBKETAMA_COMPATIBLE, true);
        }

        if ($this->memcached->getServerList() === []) {
            $this->memcached->setOption(Memcached::OPT_BINARY_PROTOCOL, true);
            $this->memcached->setOption(
                Memcached::OPT_CONNECT_TIMEOUT,
                (int) ($config['connect_timeout'] ?? 2000)
            );

            if (count($servers) > 1) {
                $this->memcached->setOption(Memcached::OPT_LIBKETAMA_COMPATIBLE, true);
            }

            $this->addServers($servers);
        }
    }

    public function hit(
        string $key,
        int $maxAttempts,
        int $windowSeconds
    ): RateLimitResult {
        $counterKey = $key . ':count';
        $resetKey = $key . ':reset';
        $now = time();

        $attempts = $this->memcached->increment(
            $counterKey,
            1,
            1,
            $windowSeconds
        );

        if ($attempts === false) {
            throw new RuntimeException(
                'Memcached rate-limit operation failed: '
                . $this->memcached->getResultMessage()
            );
        }

        $attempts = (int) $attempts;

        if ($attempts === 1) {
            $resetAt = $now + $windowSeconds;

            if (
                !$this->memcached->set(
                    $resetKey,
                    $resetAt,
                    $windowSeconds
                )
            ) {
                throw new RuntimeException(
                    'Unable to store Memcached reset time: '
                    . $this->memcached->getResultMessage()
                );
            }
        } else {
            $resetAt = $this->memcached->get($resetKey);

            if ($resetAt === false) {
                $resultCode = $this->memcached->getResultCode();

                if ($resultCode !== Memcached::RES_NOTFOUND) {
                    throw new RuntimeException(
                        'Unable to read Memcached reset time: '
                        . $this->memcached->getResultMessage()
                    );
                }

                $resetAt = $now + $windowSeconds;
                $this->memcached->add(
                    $resetKey,
                    $resetAt,
                    $windowSeconds
                );

                $storedReset = $this->memcached->get($resetKey);

                if ($storedReset !== false) {
                    $resetAt = $storedReset;
                }
            }
        }

        return RateLimitResult::fromCounter(
            $attempts,
            $maxAttempts,
            (int) $resetAt,
            $now
        );
    }

    public function clear(string $key): void
    {
        foreach ([$key . ':count', $key . ':reset'] as $item) {
            $deleted = $this->memcached->delete($item);

            if (
                !$deleted
                && $this->memcached->getResultCode()
                    !== Memcached::RES_NOTFOUND
            ) {
                throw new RuntimeException(
                    'Unable to clear Memcached rate-limit counter: '
                    . $this->memcached->getResultMessage()
                );
            }
        }
    }

    private function addServers(array $servers): void
    {
        $normalized = [];

        foreach ($servers as $server) {
            $host = trim((string) ($server['host']));
            $port = (int) ($server['port']);
            $weight = (int) ($server['weight']);

            if ($host === '') {
                throw new RuntimeException('Memcached server host cannot be empty.');
            }

            if ($port < 1 || $port > 65535) {
                throw new RuntimeException("Invalid Memcached server port: {$port}.");
            }

            if ($weight < 0) {
                throw new RuntimeException('Memcached server weight cannot be negative.');
            }

            $normalized[] = [$host, $port, $weight];
        }

        if (!$this->memcached->addServers($normalized)) {
            throw new RuntimeException('Unable to configure Memcached servers.');
        }
    }
}
