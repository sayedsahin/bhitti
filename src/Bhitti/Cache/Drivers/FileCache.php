<?php
declare(strict_types=1);

namespace Bhitti\Cache\Drivers;

use Bhitti\Cache\CacheInterface;
use RuntimeException;

final class FileCache implements CacheInterface
{
    private string $path;

    public function __construct(string $path)
    {
        $this->path = rtrim($path, '/');

        if (!is_dir($path) && !mkdir($path, 0775, true) && !is_dir($path)) {
            throw new RuntimeException(
                "Unable to create cache directory: {$path}"
            );
        }

        if (!is_writable($path)) {
            throw new RuntimeException(
                "Cache directory is not writable: {$path}"
            );
        }

        $this->path = $path;
    }

    private function file(string $key): string
    {
        return $this->path . '/' . md5($key) . '.cache';
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $file = $this->file($key);

        $handle = @fopen($file, 'rb');

        if ($handle === false) {
            return $default;
        }

        $locked = false;

        try {
            if (!flock($handle, LOCK_SH)) {
                return $default;
            }

            $locked = true;
            $payload = stream_get_contents($handle);

        } finally {
            if ($locked) {
                flock($handle, LOCK_UN);
            }

            fclose($handle);
        }

        if (!is_string($payload) || $payload === '') {
            return $default;
        }

        $data = @unserialize($payload);

        if (!is_array($data) || !array_key_exists('value', $data) || !array_key_exists('expires', $data)) {
            return $default;
        }

        $expires = (int) $data['expires'];

        if ($expires !== 0 && $expires <= time()) {
            // @unlink($file);
            return $default;
        }

        return $data['value'];
    }

    public function put(string $key, mixed $value, int $ttl = 0): void
    {
        $file = $this->file($key);

        $payload = serialize([
            'value' => $value,
            'expires' => $ttl > 0
                ? time() + $ttl
                : 0,
        ]);

        /*
         * c+b don't file truncate
         * First lock, then truncate/write
         */
        $handle = @fopen($file, 'c+b');

        if ($handle === false) {
            throw new RuntimeException(
                "Unable to open cache file: {$file}"
            );
        }

        $locked = false;

        try {
            if (!flock($handle, LOCK_EX)) {
                throw new RuntimeException(
                    "Unable to lock cache file: {$file}"
                );
            }

            $locked = true;

            if (!ftruncate($handle, 0) || !rewind($handle)) {
                throw new RuntimeException(
                    "Unable to reset cache file: {$file}"
                );
            }

            $length = strlen($payload);
            $written = 0;

            while ($written < $length) {
                $bytes = fwrite(
                    $handle,
                    substr($payload, $written)
                );

                if (
                    $bytes === false
                    || $bytes === 0
                ) {
                    throw new RuntimeException(
                        "Unable to write cache file: {$file}"
                    );
                }

                $written += $bytes;
            }

            if (!fflush($handle)) {
                throw new RuntimeException(
                    "Unable to flush cache file: {$file}"
                );
            }
        } finally {
            if ($locked) {
                flock($handle, LOCK_UN);
            }

            fclose($handle);
        }
    }

    public function has(string $key): bool
    {
        $missing = new \stdClass();

        return $this->get($key, $missing) !== $missing;
    }

    public function forget(string $key): void
    {
        $file = $this->file($key);

        if (file_exists($file)) {
            @unlink($file);
        }
    }

    public function flush(): void
    {
        foreach (glob($this->path . '/*.cache') ?: [] as $file) {
            @unlink($file);
        }
    }
}