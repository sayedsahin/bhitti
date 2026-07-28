<?php

declare(strict_types=1);

namespace Bhitti\RateLimit\RateLimitDriver;

use Bhitti\RateLimit\RateLimitResult;
use JsonException;
use RuntimeException;

final class FileDriver implements RateLimitDriverInterface
{
    private string $directory;

    public function __construct()
    {
        $directory = (string) config(
            'rate_limit.file.path',
            STORAGE_PATH . '/cache/rate-limit'
        );

        $directory = rtrim($directory, '/\\');

        if ($directory === '') {
            throw new RuntimeException(
                'Rate-limit file directory cannot be empty.'
            );
        }

        if (
            !is_dir($directory)
            && !mkdir($directory, 0775, true)
            && !is_dir($directory)
        ) {
            throw new RuntimeException(
                "Unable to create rate-limit directory: {$directory}"
            );
        }

        if (!is_writable($directory)) {
            throw new RuntimeException(
                "Rate-limit directory is not writable: {$directory}"
            );
        }

        $this->directory = $directory;
        $this->collectGarbage();
    }

    public function hit(
        string $key,
        int $maxAttempts,
        int $windowSeconds
    ): RateLimitResult {
        $file = $this->filePath($key);
        $handle = @fopen($file, 'c+');

        if ($handle === false) {
            throw new RuntimeException(
                "Unable to open rate-limit file: {$file}"
            );
        }

        $locked = false;

        try {
            if (!flock($handle, LOCK_EX)) {
                throw new RuntimeException(
                    "Unable to lock rate-limit file: {$file}"
                );
            }

            $locked = true;
            rewind($handle);

            $contents = stream_get_contents($handle);
            $now = time();
            $attempts = 1;
            $resetAt = $now + $windowSeconds;

            if (is_string($contents) && trim($contents) !== '') {
                try {
                    $data = json_decode(
                        $contents,
                        true,
                        512,
                        JSON_THROW_ON_ERROR
                    );
                } catch (JsonException) {
                    $data = null;
                }

                if (
                    is_array($data)
                    && isset($data['attempts'], $data['reset_at'])
                    && is_numeric($data['attempts'])
                    && is_numeric($data['reset_at'])
                    && (int) $data['reset_at'] > $now
                ) {
                    $attempts = (int) $data['attempts'] + 1;
                    $resetAt = (int) $data['reset_at'];
                }
            }

            try {
                $payload = json_encode(
                    [
                        'attempts' => $attempts,
                        'reset_at' => $resetAt,
                    ],
                    JSON_THROW_ON_ERROR
                );
            } catch (JsonException $exception) {
                throw new RuntimeException(
                    'Unable to encode rate-limit state.',
                    0,
                    $exception
                );
            }

            rewind($handle);

            if (!ftruncate($handle, 0)) {
                throw new RuntimeException(
                    "Unable to truncate rate-limit file: {$file}"
                );
            }

            $this->writeAll($handle, $payload, $file);

            if (!fflush($handle)) {
                throw new RuntimeException(
                    "Unable to flush rate-limit file: {$file}"
                );
            }

            return RateLimitResult::fromCounter(
                $attempts,
                $maxAttempts,
                $resetAt,
                $now
            );
        } finally {
            if ($locked) {
                flock($handle, LOCK_UN);
            }

            fclose($handle);
        }
    }

    public function clear(string $key): void
    {
        $file = $this->filePath($key);
        $handle = @fopen($file, 'c+');

        if ($handle === false) {
            throw new RuntimeException(
                "Unable to open rate-limit file: {$file}"
            );
        }

        $locked = false;

        try {
            if (!flock($handle, LOCK_EX)) {
                throw new RuntimeException(
                    "Unable to lock rate-limit file: {$file}"
                );
            }

            $locked = true;
            rewind($handle);

            if (!ftruncate($handle, 0) || !fflush($handle)) {
                throw new RuntimeException(
                    "Unable to clear rate-limit file: {$file}"
                );
            }
        } finally {
            if ($locked) {
                flock($handle, LOCK_UN);
            }

            fclose($handle);
        }
    }

    private function filePath(string $key): string
    {
        return $this->directory
            . DIRECTORY_SEPARATOR
            . hash('sha256', $key)
            . '.rate';
    }

    private function collectGarbage(): void
    {
        $probability = (int) config(
            'rate_limit.file.cleanup_probability',
            1
        );

        if ($probability < 1) {
            return;
        }

        $probability = min(100, $probability);

        try {
            if (random_int(1, 100) > $probability) {
                return;
            }
        } catch (\Throwable) {
            return;
        }

        $staleAfter = max(3600, (int) config(
            'rate_limit.file.stale_after_seconds',
            86400
        ));
        $deleteLimit = max(1, (int) config(
            'rate_limit.file.cleanup_delete_limit',
            100
        ));
        $threshold = time() - $staleAfter;
        $deleted = 0;
        $files = glob(
            $this->directory . DIRECTORY_SEPARATOR . '*.rate'
        ) ?: [];

        foreach ($files as $file) {
            if ($deleted >= $deleteLimit) {
                break;
            }

            $modifiedAt = @filemtime($file);

            if ($modifiedAt !== false && $modifiedAt < $threshold) {
                if (@unlink($file)) {
                    $deleted++;
                }
            }
        }
    }

    /**
     * @param resource $handle
     */
    private function writeAll($handle, string $payload, string $file): void
    {
        $length = strlen($payload);
        $written = 0;

        while ($written < $length) {
            $bytes = fwrite($handle, substr($payload, $written));

            if ($bytes === false || $bytes === 0) {
                throw new RuntimeException(
                    "Unable to write rate-limit file: {$file}"
                );
            }

            $written += $bytes;
        }
    }
}
