<?php

declare(strict_types=1);

namespace Bhitti\RateLimit\RateLimitDriver;

use Bhitti\RateLimit\RateLimitResult;
use RuntimeException;

final class ApcuDriver implements RateLimitDriverInterface
{
    public function __construct()
    {
        if (
            !function_exists('apcu_enabled')
            || !apcu_enabled()
            || !function_exists('apcu_add')
            || !function_exists('apcu_inc')
        ) {
            throw new RuntimeException(
                'APCu extension is not enabled.'
            );
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

        if (apcu_add($counterKey, 1, $windowSeconds)) {
            $resetAt = $now + $windowSeconds;
            apcu_store($resetKey, $resetAt, $windowSeconds);

            return RateLimitResult::fromCounter(
                1,
                $maxAttempts,
                $resetAt,
                $now
            );
        }

        $attempts = apcu_inc($counterKey, 1, $success);

        if (!$success) {
            if (apcu_add($counterKey, 1, $windowSeconds)) {
                $resetAt = $now + $windowSeconds;
                apcu_store($resetKey, $resetAt, $windowSeconds);

                return RateLimitResult::fromCounter(
                    1,
                    $maxAttempts,
                    $resetAt,
                    $now
                );
            }

            $attempts = apcu_inc($counterKey, 1, $success);
        }

        if (!$success || !is_int($attempts)) {
            throw new RuntimeException(
                'Unable to update APCu rate-limit counter.'
            );
        }

        $resetAt = apcu_fetch($resetKey, $resetFound);

        if (
            !$resetFound
            || !is_int($resetAt)
            || $resetAt <= $now
        ) {
            $resetAt = $now + $windowSeconds;
            apcu_store($resetKey, $resetAt, $windowSeconds);
        }

        return RateLimitResult::fromCounter(
            $attempts,
            $maxAttempts,
            $resetAt,
            $now
        );
    }

    public function clear(string $key): void
    {
        apcu_delete([
            $key . ':count',
            $key . ':reset',
        ]);
    }
}
