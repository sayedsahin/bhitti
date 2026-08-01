<?php

declare(strict_types=1);

namespace App\Middlewares;

use Bhitti\RateLimit\RateLimiter;
use Bhitti\RateLimit\RateLimitResult;
use Bhitti\Http\Middleware\MiddlewareInterface;
use Bhitti\Http\Response;
use Bhitti\Session\Session;

final class RateLimit implements MiddlewareInterface
{
    public function handle(): ?Response
    {
        [$key, $maxAttempts, $windowSeconds] = $this->policy();

        $result = RateLimiter::hit(
            $key,
            $maxAttempts,
            $windowSeconds
        );

        if (!$result->allowed()) {
            return $this->reject($result);
        }

        $this->sendHeaders($result);

        return null;
    }

    private function policy(): array
    {
        $method = strtoupper(request()->method());

        $path = request()->path();

        $signature = $method . ' ' . $path;

        $sensitive = config(
            'rate_limit.sensitive_routes',
            []
        );

        $routes = is_array($sensitive)
            ? ($sensitive['routes'] ?? [])
            : [];

        if (
            is_array($routes)
            && in_array($signature, $routes, true)
        ) {
            return [
                'sensitive:'
                    . $signature
                    . ':ip:'
                    . $this->ip(),

                (int) ($sensitive['max_attempts'] ?? 5),

                (int) ($sensitive['window_seconds'] ?? 60),
            ];
        }

        return is_api_request()
            ? $this->apiPolicy()
            : $this->webPolicy();
    }

    private function apiPolicy(): array
    {
        return [
            'api:ip:' . $this->ip(),
            (int) config(
                'rate_limit.api.max_attempts',
                120
            ),
            (int) config(
                'rate_limit.api.window_seconds',
                60
            ),
        ];
    }

    private function webPolicy(): array
    {
        $user_id = Session::get('auth_user_id');

        if ($user_id !== null) {
            return [
                'web:user:' . (int) $user_id,
                (int) config(
                    'rate_limit.web.authenticated.max_attempts',
                    240
                ),
                (int) config(
                    'rate_limit.web.authenticated.window_seconds',
                    60
                ),
            ];
        }

        return [
            'web:ip:' . $this->ip(),
            (int) config(
                'rate_limit.web.guest.max_attempts',
                120
            ),
            (int) config(
                'rate_limit.web.guest.window_seconds',
                60
            ),
        ];
    }

    private function ip(): string
    {
        $ip = request()->ip();

        return is_string($ip) && $ip !== ''
            ? $ip
            : '0.0.0.0';
    }

    private function sendHeaders(RateLimitResult $result): void
    {
        if (headers_sent()) {
            return;
        }

        header('X-RateLimit-Limit: ' . $result->limit());
        header(
            'X-RateLimit-Remaining: '
            . $result->remaining()
        );
        header('X-RateLimit-Reset: ' . $result->resetAt());
    }

    private function reject(RateLimitResult $result): Response
    {
        $retryAfter = max(
            1,
            $result->retryAfter()
        );

        $headers = [
            'Retry-After' => (string) $retryAfter,
            'X-RateLimit-Limit' => (string) $result->limit(),
            'X-RateLimit-Remaining' => (string) $result->remaining(),
            'X-RateLimit-Reset' => (string) $result->resetAt(),
        ];

        if (is_api_request()) {
            return response()
                ->json([
                    'error' => 'Too many requests',
                    'retry_after' => $retryAfter,
                ], 429)
                ->headers($headers);
        }
        
        return response()
            ->html(
                'Too many requests. Please try again later.',
                429
            )
            ->headers($headers);
    }
}
