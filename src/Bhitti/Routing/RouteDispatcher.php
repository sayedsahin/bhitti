<?php

declare(strict_types=1);

namespace Bhitti\Routing;

use Bhitti\Core\Container;
use Bhitti\Http\Middleware\MiddlewareKernel;
use Bhitti\Http\Response;
use FastRoute\Dispatcher as FastRouteDispatcher;

final class RouteDispatcher
{
    public function __construct(
        private Container $container,
        private MiddlewareKernel $middleware
    ) {
    }

    public function dispatch(array $routeInfo, bool $isApi): void
    {
        switch ($routeInfo[0]) {
            case FastRouteDispatcher::NOT_FOUND:
                $this->notFound($isApi);
                return;

            case FastRouteDispatcher::METHOD_NOT_ALLOWED:
                $this->methodNotAllowed($routeInfo[1], $isApi);
                return;

            case FastRouteDispatcher::FOUND:
                $this->found($routeInfo[1], $routeInfo[2], $isApi);
                return;
        }
    }

    private function notFound(bool $isApi, ?string $message = 'Not Found'): void
    {
        if ($isApi) {
            response()->json([
                'error' => $message,
            ], 404)->send();

            return;
        }

        response()->html($message, 404)->send();
    }

    private function methodNotAllowed(array $allowedMethods, bool $isApi): void
    {
        $allow = implode(', ', $allowedMethods);

        if ($isApi) {
            response()->json([
                'error' => 'Method Not Allowed',
            ], 405)->header('Allow', $allow)->send();

            return;
        }

        response()->html('Method Not Allowed', 405)->header('Allow', $allow)->send();
    }

    private function badRequest(bool $isApi, string $message): void
    {
        if ($isApi) {
            response()->json([
                'error' => $message,
            ], 400)->send();

            return;
        }

        response()->html($message, 400)->send();
    }

    private function found(array $handler, array $vars, bool $isApi): void
    {
        $middlewareResponse = $this->middleware->handle(
            $handler[2] ?? []
        );

        if ($middlewareResponse instanceof Response) {
            $middlewareResponse->send();

            return;
        }

        $controller = $this->container->make($handler[0]);

        $arguments = $this->routeArguments(
            $handler[3] ?? null,
            $vars
        );

        if ($arguments === null) {
            $this->badRequest($isApi, 'Parameter Type Error');
            return;
        }

        $result = $controller->{$handler[1]}(...$arguments);

        if ($result instanceof Response) {
            $result->send();

            return;
        }

        if (is_string($result)) {
            echo $result;
        }
    }

    private function routeArguments(string|array|null $metadata, array $vars): ?array
    {
        $arguments = array_values($vars);

        if ($metadata === null) {
            return $arguments;
        }

        $types = (array) $metadata;

        foreach ($arguments as $index => $value) {
            $type = $types[$index] ?? null;

            $validated = match ($type) {
                'int'   => filter_var($value, FILTER_VALIDATE_INT),
                'float' => filter_var($value, FILTER_VALIDATE_FLOAT),
                'bool'  => filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE),
                default => $value,
            };

            $invalid = match ($type) {
                'int', 'float' => $validated === false,
                'bool' => $validated === null,
                default => false,
            };

            if ($invalid) {
                return null;
            }

            $arguments[$index] = $validated;
        }

        return $arguments;
    }
}