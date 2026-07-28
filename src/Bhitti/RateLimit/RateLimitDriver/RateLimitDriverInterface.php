<?php

declare(strict_types=1);

namespace Bhitti\RateLimit\RateLimitDriver;

use Bhitti\RateLimit\RateLimitResult;

interface RateLimitDriverInterface
{
    public function hit(
        string $key,
        int $maxAttempts,
        int $windowSeconds
    ): RateLimitResult;

    public function clear(string $key): void;
}
