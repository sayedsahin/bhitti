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
                $this->found($routeInfo[1], $routeInfo[2]);
                return;
        }
    }

    private function notFound(bool $isApi): void
    {
        if ($isApi) {
            response()->json([
                'error' => 'Not Found',
            ], 404)->send();

            return;
        }

        response()->html('Not Found', 404)->send();
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

    private function found(array $handler, array $vars): void
    {
        $middlewares = $handler[2] ?? [];

        $middlewareResponse = $this->middleware->handle($middlewares);

        if ($middlewareResponse instanceof Response) {
            $middlewareResponse->send();
            return;
        }

        $controller = $this->container->make($handler[0]);
        $action = $handler[1];

        $result = $controller->$action(...array_values($vars));

        if ($result instanceof Response) {
            $result->send();
            return;
        }

        /*
         * Temporary support while view() directly renders output.
         */
        if (is_string($result)) {
            echo $result;
        }
    }
}