<?php

declare(strict_types=1);

namespace Bhitti\Session\Drivers;

use Bhitti\Http\TrustedProxy;
use Bhitti\Session\SessionInterface;
use Redis;
use RedisException;
use RuntimeException;
use Throwable;

final class RedisSession implements SessionInterface
{
    private ?Redis $redis = null;
    private bool $started = false;
    private bool $handlerRegistered = false;
    private ?string $lockedSessionId = null;
    private ?string $lockToken = null;

    public function __construct(
        private readonly array $sessionConfig,
        private readonly array $redisConfig
    ) {
    }

    public function start(): void
    {
        if ($this->started && session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            $this->started = true;
            return;
        }

        $this->registerHandler();

        $name = (string) ($this->sessionConfig['name'] ?? 'BHITTISESSID');

        if ($name !== '') {
            session_name($name);
        }

        $started = session_start([
            'use_strict_mode' => 1,
            'use_only_cookies' => 1,
            'cookie_lifetime' => (int) ($this->sessionConfig['cookie_lifetime'] ?? 0),
            'cookie_path' => (string) ($this->sessionConfig['path'] ?? '/'),
            'cookie_domain' => (string) ($this->sessionConfig['domain'] ?? ''),
            'cookie_httponly' => true,
            'cookie_secure' => (bool) ($this->sessionConfig['secure'] ?? true)
                && TrustedProxy::isSecureRequest($_SERVER),
            'cookie_samesite' => (string) ($this->sessionConfig['samesite'] ?? 'Lax'),
            'gc_maxlifetime' => $this->lifetime(),
        ]);

        if (!$started) {
            throw new RuntimeException('Unable to start Redis session.');
        }

        $this->started = true;
    }

    public function get(string $key, mixed $default = null): mixed
    {
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

        if (!session_regenerate_id(true)) {
            throw new RuntimeException('Unable to regenerate Redis session ID.');
        }
    }

    public function destroy(): void
    {
        $this->start();
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();

            setcookie(session_name(), '', [
                'expires' => time() - 3600,
                'path' => $params['path'],
                'domain' => $params['domain'],
                'secure' => $params['secure'],
                'httponly' => $params['httponly'],
                'samesite' => $params['samesite'] ?? 'Lax',
            ]);
        }

        session_destroy();
        $this->releaseLock();
        $this->started = false;
    }

    public function close(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            $this->releaseLock();
            $this->started = false;
            return;
        }

        session_write_close();
        $this->started = false;
    }

    private function registerHandler(): void
    {
        if ($this->handlerRegistered) {
            return;
        }

        $registered = session_set_save_handler(
            fn(string $savePath, string $sessionName): bool => true,
            function (): bool {
                $this->releaseLock();
                return true;
            },
            fn(string $id): string => $this->readSession($id),
            fn(string $id, string $data): bool => $this->writeSession($id, $data),
            fn(string $id): bool => $this->destroySession($id),
            fn(int $maxLifetime): int => 0
        );

        if (!$registered) {
            throw new RuntimeException('Unable to register Redis session handler.');
        }

        $this->handlerRegistered = true;
    }

    private function readSession(string $id): string
    {
        $this->acquireLock($id);

        try {
            $value = $this->redis()->get($this->sessionKey($id));
        } catch (RedisException $exception) {
            throw $this->connectionException($exception);
        }

        return is_string($value) ? $value : '';
    }

    private function writeSession(string $id, string $data): bool
    {
        try {
            return $this->redis()->setex(
                $this->sessionKey($id),
                $this->lifetime(),
                $data
            );
        } catch (RedisException $exception) {
            throw $this->connectionException($exception);
        }
    }

    private function destroySession(string $id): bool
    {
        try {
            $this->redis()->del($this->sessionKey($id));
            return true;
        } catch (RedisException $exception) {
            throw $this->connectionException($exception);
        } finally {
            if ($this->lockedSessionId === $id) {
                $this->releaseLock();
            }
        }
    }

    private function validateSessionId(string $id): bool
    {
        try {
            return $this->redis()->exists($this->sessionKey($id)) > 0;
        } catch (RedisException $exception) {
            throw $this->connectionException($exception);
        }
    }

    private function updateTimestamp(string $id, string $data): bool
    {
        try {
            $redis = $this->redis();
            $key = $this->sessionKey($id);

            if ($redis->exists($key) > 0) {
                return $redis->expire($key, $this->lifetime());
            }

            return $redis->setex($key, $this->lifetime(), $data);
        } catch (RedisException $exception) {
            throw $this->connectionException($exception);
        }
    }

    private function redis(): Redis
    {
        if ($this->redis !== null) {
            return $this->redis;
        }

        if (!class_exists(Redis::class)) {
            throw new RuntimeException(
                'Redis session driver requires the PHP Redis extension.'
            );
        }

        $redis = new Redis();
        $host = (string) ($this->redisConfig['host'] ?? '127.0.0.1');
        $port = (int) ($this->redisConfig['port'] ?? 6379);
        $timeout = (float) ($this->redisConfig['timeout'] ?? 2.0);
        $readTimeout = (float) ($this->redisConfig['read_timeout'] ?? 2.0);

        try {
            if (!$redis->connect($host, $port, $timeout)) {
                throw new RuntimeException(
                    "Unable to connect to Redis at {$host}:{$port}."
                );
            }

            if (defined('Redis::OPT_READ_TIMEOUT')) {
                $redis->setOption(Redis::OPT_READ_TIMEOUT, $readTimeout);
            }

            $username = $this->redisConfig['username'] ?? null;
            $password = $this->redisConfig['password'] ?? null;

            if ($password !== null && $password !== '') {
                $credentials = $username !== null && $username !== ''
                    ? [(string) $username, (string) $password]
                    : (string) $password;

                if (!$redis->auth($credentials)) {
                    throw new RuntimeException('Redis authentication failed.');
                }
            }

            $database = (int) ($this->redisConfig['session_db'] ?? 3);

            if (!$redis->select($database)) {
                throw new RuntimeException(
                    "Unable to select Redis database {$database}."
                );
            }
        } catch (RedisException $exception) {
            throw $this->connectionException($exception);
        }

        return $this->redis = $redis;
    }

    private function acquireLock(string $id): void
    {
        $config = (array) ($this->sessionConfig['redis'] ?? []);

        if (!(bool) ($this->sessionConfig['lock'] ?? true)) {
            return;
        }

        if ($this->lockedSessionId === $id) {
            return;
        }

        $redis = $this->redis();
        $lockKey = $this->lockKey($id);
        $token = bin2hex(random_bytes(16));
        $ttl = max(1000, (int) (($this->sessionConfig['lock_ttl'] ?? 10) * 1000));
        $wait = max(0.0, (float) ($this->sessionConfig['lock_wait'] ?? 2.0));
        $sleep = max(1000, (int) ($this->sessionConfig['lock_sleep'] ?? 20000));
        $deadline = microtime(true) + $wait;

        do {
            try {
                $locked = $redis->set($lockKey, $token, [
                    'nx',
                    'px' => $ttl,
                ]);
            } catch (RedisException $exception) {
                throw $this->connectionException($exception);
            }

            if ($locked === true) {
                $this->lockedSessionId = $id;
                $this->lockToken = $token;
                return;
            }

            if ($wait <= 0.0) {
                break;
            }

            usleep($sleep);
        } while (microtime(true) < $deadline);

        throw new RuntimeException(
            'Unable to acquire Redis session lock.'
        );
    }

    private function releaseLock(): void
    {
        if ($this->lockedSessionId === null || $this->lockToken === null) {
            return;
        }

        $lockKey = $this->lockKey($this->lockedSessionId);
        $token = $this->lockToken;

        $this->lockedSessionId = null;
        $this->lockToken = null;

        if ($this->redis === null) {
            return;
        }

        try {
            $this->redis->eval(
                <<<'LUA'
if redis.call('get', KEYS[1]) == ARGV[1] then
    return redis.call('del', KEYS[1])
end
return 0
LUA,
                [$lockKey, $token],
                1
            );
        } catch (Throwable) {
            // The lock expires automatically through its TTL.
        }
    }

    private function sessionKey(string $id): string
    {
        return $this->prefix() . $id;
    }

    private function lockKey(string $id): string
    {
        return $this->prefix() . 'lock:' . $id;
    }

    private function prefix(): string
    {

        return (string) $this->sessionConfig['prefix'] ?? 'app_session:';
    }

    private function lifetime(): int
    {
        return max(1, (int) ($this->sessionConfig['lifetime'] ?? 7200));
    }

    private function connectionException(RedisException $exception): RuntimeException
    {
        return new RuntimeException(
            'Redis session operation failed: ' . $exception->getMessage(),
            0,
            $exception
        );
    }
}
