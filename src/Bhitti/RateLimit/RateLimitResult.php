<?php

declare(strict_types=1);

namespace Bhitti\RateLimit;

final class RateLimitResult
{
    public function __construct(
        private bool $allowed,
        private int $limit,
        private int $attempts,
        private int $remaining,
        private int $retryAfter,
        private int $resetAt
    ) {
    }

    public static function fromCounter(
        int $attempts,
        int $maxAttempts,
        int $resetAt,
        int $now
    ): self {
        $allowed = $attempts <= $maxAttempts;
        $remaining = max(0, $maxAttempts - $attempts);
        $retryAfter = max(0, $resetAt - $now);

        if (!$allowed && $retryAfter === 0) {
            $retryAfter = 1;
        }

        return new self(
            $allowed,
            $maxAttempts,
            $attempts,
            $remaining,
            $retryAfter,
            $resetAt
        );
    }

    public function allowed(): bool
    {
        return $this->allowed;
    }

    public function limit(): int
    {
        return $this->limit;
    }

    public function attempts(): int
    {
        return $this->attempts;
    }

    public function remaining(): int
    {
        return $this->remaining;
    }

    public function retryAfter(): int
    {
        return $this->retryAfter;
    }

    public function resetAt(): int
    {
        return $this->resetAt;
    }
}
