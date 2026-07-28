<?php
declare(strict_types=1);

namespace Bhitti\Cache\Drivers;

use Bhitti\Cache\CacheInterface;
use Redis;
use RuntimeException;

final class RedisCache implements CacheInterface
{
    private ?Redis $redis = null;
    private array $config;
    private string $prefix;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->prefix = $config['prefix'] ?? 'cache:';
    }

    private function redis(): Redis
    {
        if ($this->redis !== null) {
            return $this->redis;
        }

        $redis = new Redis();

        $host = (string) ($this->config['host'] ?? '127.0.0.1');
        $port = (int) ($this->config['port'] ?? 6379);
        $timeout = (float) ($this->config['timeout'] ?? 2.0);
        $readTimeout = (float) ($this->config['read_timeout'] ?? 2.0);

        try {
            if (!$redis->connect($host, $port, $timeout)) {
                throw new RuntimeException("Unable to connect to Redis at {$host}:{$port}.");
            }

            if (defined('Redis::OPT_READ_TIMEOUT')) {
                $redis->setOption(Redis::OPT_READ_TIMEOUT, $readTimeout);
            }

            $username = $this->config['username'] ?? null;
            $password = $this->config['password'] ?? null;

            if ($password !== null && $password !== '') {
                $credentials = $username !== null && $username !== ''
                    ? [(string) $username, (string) $password]
                    : (string) $password;

                if (!$redis->auth($credentials)) {
                    throw new RuntimeException('Redis authentication failed.');
                }
            }

            $database = (int) (
                $this->config['cache_db']
                ?? $this->config['db']
                ?? 0
            );

            if (!$redis->select($database)) {
                throw new RuntimeException("Unable to select Redis database {$database}.");
            }
        } catch (\RedisException $exception) {
            throw new RuntimeException(
                'Redis cache connection failed: ' . $exception->getMessage(),
                0,
                $exception
            );
        }

        return $this->redis = $redis;
    }

    private function key(string $key): string
    {
        return $this->prefix . $key;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->redis()->get($this->key($key));

        if ($value === false) {
            return $default;
        }

        try {
            return unserialize($value);
        } catch (\Throwable) {
            return $default;
        }
    }

    public function put(string $key, mixed $value, int $ttl = 0): void
    {
        $payload = serialize($value);
        $key = $this->key($key);

        if ($ttl > 0) {
            $this->redis()->setex($key, $ttl, $payload);
        } else {
            $this->redis()->set($key, $payload);
        }
    }

    public function has(string $key): bool
    {
        return $this->redis()->exists($this->key($key)) > 0;
    }

    public function forget(string $key): void
    {
        $this->redis()->del($this->key($key));
    }

    public function flush(): void
    {
        // ⚠️ Safe flush (prefix-based, not full DB wipe)

        $redis = $this->redis();
        $prefix = $this->prefix;

        $iterator = null;

        do {
            $keys = $redis->scan($iterator, $prefix . '*', 100);

            if (is_array($keys) && $keys !== []) {
                $redis->del($keys);
            }
        } while ($iterator !== 0 && $iterator !== '0');
    }
}