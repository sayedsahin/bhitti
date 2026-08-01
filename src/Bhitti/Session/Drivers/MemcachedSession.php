<?php

declare(strict_types=1);

namespace Bhitti\Session\Drivers;

use Bhitti\Session\SessionInterface;
use Memcached;
use RuntimeException;
use Throwable;

final class MemcachedSession implements SessionInterface
{
    private ?Memcached $memcached = null;

    private bool $started = false;

    private bool $handlerRegistered = false;

    private ?string $lockedSessionId = null;

    private ?string $lockToken = null;

    public function __construct(
        private readonly array $sessionConfig,
        private readonly array $memcachedConfig
    ) {
    }

    public function start(): void
    {
        if ($this->started) {
            return;
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            $this->started = true;
            return;
        }

        $this->registerHandler();

        session_name(
            (string) ($this->sessionConfig['name'] ?? 'BHITTISESSID')
        );

        if (!session_start([
            'cookie_lifetime' => 0,
            'cookie_httponly' => true,
            'cookie_samesite' => $this->sessionConfig['samesite'] ?? 'Lax',
            'gc_maxlifetime' => $this->lifetime(),
        ])) {
            throw new RuntimeException(
                'Unable to start Memcached session.'
            );
        }

        $this->started = true;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $this->start();

        return $_SESSION[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $this->start();

        $_SESSION[$key] = $value;
    }

    public function forget(string $key): void
    {
        $this->start();

        unset($_SESSION[$key]);
    }

    public function flush(): void
    {
        $this->start();

        $_SESSION = [];
    }

    public function regenerate(): void
    {
        $this->start();

        session_regenerate_id(true);
    }

    public function destroy(): void
    {
        $this->start();

        $_SESSION = [];

        session_destroy();

        $this->releaseLock();

        $this->started = false;
    }

    public function close(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        $this->releaseLock();
        $this->started = false;
    }

    private function registerHandler(): void
    {
        if ($this->handlerRegistered) {
            return;
        }

        session_set_save_handler(
            fn() => true,
            fn() => $this->releaseLock(),
            fn(string $id) => $this->read($id),
            fn(string $id, string $data) => $this->write($id, $data),
            fn(string $id) => $this->delete($id),
            fn() => true
        );

        $this->handlerRegistered = true;
    }

    private function read(string $id): string
    {
        $this->acquireLock($id);

        $data = $this->client()->get(
            $this->key($id)
        );

        return is_string($data) ? $data : '';
    }

    private function write(string $id, string $data): bool
    {
        return $this->client()->set(
            $this->key($id),
            $data,
            $this->lifetime()
        );
    }

    private function delete(string $id): bool
    {
        $this->releaseLock();

        return $this->client()->delete(
            $this->key($id)
        );
    }

    private function client(): Memcached
    {
        if ($this->memcached !== null) {
            return $this->memcached;
        }

        if (!class_exists(Memcached::class)) {
            throw new RuntimeException(
                'PHP Memcached extension is required.'
            );
        }

        $client = new Memcached();

        $servers = $this->memcachedConfig['servers'] ?? [];

        if (!$client->addServers($servers)) {
            throw new RuntimeException(
                'Unable to connect Memcached servers.'
            );
        }

        return $this->memcached = $client;
    }

    private function key(string $id): string
    {
        return rtrim(
            (string) ($this->sessionConfig['prefix'] ?? 'bhitti:session:'),
            ':'
        ) . ':' . $id;
    }

    private function lockKey(string $id): string
    {
        return $this->key($id) . ':lock';
    }

    private function acquireLock(string $id): void
    {
        if (!($this->sessionConfig['lock'] ?? true)) {
            return;
        }

        $token = bin2hex(random_bytes(16));

        if ($this->client()->add(
            $this->lockKey($id),
            $token,
            (int) ($this->sessionConfig['lock_ttl'] ?? 10)
        )) {
            $this->lockedSessionId = $id;
            $this->lockToken = $token;
        }
    }

    private function releaseLock(): void
    {
        if (!$this->lockedSessionId) {
            return;
        }

        try {
            $this->client()->delete(
                $this->lockKey($this->lockedSessionId)
            );
        } catch (Throwable) {
            // Lock expires automatically.
        }

        $this->lockedSessionId = null;
        $this->lockToken = null;
    }

    private function lifetime(): int
    {
        return (int) (
            $this->sessionConfig['lifetime'] ?? 7200
        );
    }
}
