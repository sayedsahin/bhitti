<?php

declare(strict_types=1);

namespace Bhitti\Http\Middleware;

use Bhitti\Http\Response;
use RuntimeException;

final class MiddlewareKernel
{
    private array $web = [];
    private array $api = [];

    /**
     * Register global web middleware.
     */
    public function web(array $middlewares): void
    {
        $this->web = $middlewares;
    }

    /**
     * Register global API middleware.
     */
    public function api(array $middlewares): void
    {
        $this->api = $middlewares;
    }

    /**
     * Execute global middleware.
     *
     * A returned Response immediately terminates
     * the current request.
     */
    public function run(bool $isApi): void
    {
        $response = self::handle(
            $isApi ? $this->api : $this->web
        );

        if ($response instanceof Response) {
            $response->send();
            exit;
        }
    }

    /**
     * Execute a middleware stack.
     *
     * Returns the first Response produced by a middleware.
     * Returns null when every middleware allows execution
     * to continue.
     */
    public static function handle(array $middlewares): ?Response
    {
        foreach ($middlewares as $middleware) {
            $instance = self::resolve($middleware);

            $response = $instance->handle();

            if ($response instanceof Response) {
                return $response;
            }
        }

        return null;
    }

    /**
     * Resolve middleware.
     *
     * Supported formats:
     *
     * MiddlewareClass::class
     *
     * [
     *     MiddlewareClass::class,
     *     ['admin', 'editor']
     * ]
     */
    private static function resolve(string|array $middleware): object
    {
        if (is_string($middleware)) {
            return new $middleware();
        }

        $class = $middleware[0] ?? null;
        $arguments = $middleware[1] ?? [];

        if (!is_string($class)) {
            throw new RuntimeException(
                'Invalid middleware definition.'
            );
        }

        return new $class($arguments);
    }
}