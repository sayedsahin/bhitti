<?php

declare(strict_types=1);

namespace Bhitti\Routing;

use Bhitti\Core\Container;
use Bhitti\Http\Middleware\Attributes\Middleware;
use Bhitti\Http\Middleware\MiddlewareKernel;
use Bhitti\Http\Response;
use FastRoute\Dispatcher as FastRouteDispatcher;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;

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

        $controllerClass = $handler[0];
        $action = $handler[1];
        $class = new ReflectionClass($controllerClass);

        $method = $class->getMethod($action);

        $middlewares = array_merge(
            $handler[2] ?? [],
            $this->controllerMiddlewares($class, $method)
        );

        $middlewareResponse = $this->middleware->handle($middlewares);

        if ($middlewareResponse instanceof Response) {
            $middlewareResponse->send();
            return;
        }

        /*
        * Controller is instantiated only after middleware passes.
        */
        $controller = $this->container->make($controllerClass);
        $arguments = $this->routeArguments(
            $method,
            $vars
        );
        $result = $controller->$action(...$arguments);

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

    private function controllerMiddlewares(ReflectionClass $class,  ReflectionMethod $method): array
    {
        $middlewares = [];

        foreach (
            $class->getAttributes(Middleware::class)
            as $attribute
        ) {
            $definition = $attribute->newInstance();

            $middlewares[] = $definition->arguments === []
                ? $definition->class
                : [$definition->class, $definition->arguments];
        }

        foreach (
            $method->getAttributes(Middleware::class)
            as $attribute
        ) {
            $definition = $attribute->newInstance();

            $middlewares[] = $definition->arguments === []
                ? $definition->class
                : [$definition->class, $definition->arguments];
        }

        return $middlewares;
    }

    private function routeArguments(ReflectionMethod $method, array $vars): array
    {
        $arguments = array_values($vars);
        $parameters = $method->getParameters();

        foreach ($arguments as $index => $value) {
            $parameter = $parameters[$index] ?? null;

            if ($parameter === null) {
                break;
            }

            $type = $parameter->getType();

            if (!$type instanceof ReflectionNamedType || !$type->isBuiltin()) {
                continue;
            }

            $arguments[$index] = match ($type->getName()) {
                'int' => $$integer = filter_var($value, FILTER_VALIDATE_INT),
                'float' => filter_var($value, FILTER_VALIDATE_FLOAT),
                default => $value,
            };
        }

        return $arguments;
    }
}