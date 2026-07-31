<?php

declare(strict_types=1);

namespace Bhitti\Http\Middleware;

use Bhitti\Core\Container;
use Bhitti\Http\Response;
use RuntimeException;

final class MiddlewareKernel
{
    private array $web = [];
    private array $api = [];

    public function __construct(private Container $container)
    {
    }

    public function web(array $middlewares): void
    {
        $this->web = $middlewares;
    }

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
    public function handleGlobal(bool $isApi): ?Response
    {
        return $this->handle(
            $isApi ? $this->api : $this->web
        );
    }
    /**
     * Execute a middleware stack.
     *
     * Returns the first Response produced by a middleware.
     * Returns null when every middleware allows execution
     * to continue.
     */
    public function handle(array $middlewares): ?Response
    {
        foreach ($middlewares as $middleware) {
            $instance = $this->resolve($middleware);
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
    private function resolve(string|array $middleware): object
    {
        if (is_string($middleware)) {
            return $this->container->make($middleware);
        }

        $class = $middleware[0] ?? null;
        $arguments = $middleware[1] ?? [];

        if (!is_string($class)) {
            throw new RuntimeException('Invalid middleware definition.');
        }

        return new $class($arguments);
    }
}