<?php

declare(strict_types=1);

namespace Bhitti\Routing;

use Bhitti\Http\Middleware\Attributes\Middleware;
use FastRoute\RouteCollector as FastRouteCollector;
use ReflectionMethod;
use ReflectionNamedType;

final class RouteCollector extends FastRouteCollector
{
    public function addRoute($httpMethod, $route, $handler)
    {

        if (is_string($handler)) {
            $handler = [$handler, '__invoke'];
        }

        parent::addRoute(
            $httpMethod,
            $route,
            $this->prepareHandler($handler)
        );
    }

    private function prepareHandler(mixed $handler): mixed
    {
        if (!is_array($handler)) {
            return $handler;
        }

        if (array_key_exists(3, $handler)) {
            return $handler;
        }

        $controllerClass = $handler[0] ?? null;
        $action = $handler[1] ?? null;

        if (!is_string($controllerClass) || !is_string($action)) {
            return $handler;
        }

        $method = new ReflectionMethod($controllerClass, $action);

        $middlewares = array_merge(
            $handler[2] ?? [],
            $this->controllerMiddlewares($method)
        );

        $parameterTypes = $this->parameterTypes($method);

        if ($middlewares === [] && $parameterTypes === null) {
            return [
                $controllerClass,
                $action,
            ];
        }

        if ($parameterTypes === null) {
            return [
                $controllerClass,
                $action,
                $middlewares,
            ];
        }

        return [
            $controllerClass,
            $action,
            $middlewares,
            $parameterTypes,
        ];
    }

    private function controllerMiddlewares(ReflectionMethod $method): array
    {
        $middlewares = [];
        $class = $method->getDeclaringClass();

        foreach ($class->getAttributes(Middleware::class) as $attribute) {
            $definition = $attribute->newInstance();

            $middlewares[] = $definition->arguments === []
                ? $definition->class
                : [$definition->class, $definition->arguments];
        }

        foreach ($method->getAttributes(Middleware::class) as $attribute) {
            $definition = $attribute->newInstance();

            $middlewares[] = $definition->arguments === []
                ? $definition->class
                : [$definition->class, $definition->arguments];
        }

        return $middlewares;
    }

    private function parameterTypes(ReflectionMethod $method): string|array|null
    {
        $types = [];

        foreach ($method->getParameters() as $parameter) {
            $type = $parameter->getType();

            $types[] = $type instanceof ReflectionNamedType && $type->isBuiltin()
                ? $type->getName()
                : null;
        }

        if ($types === []) {
            return null;
        }

        return count($types) === 1 ? $types[0] : $types;
    }

    public function middleware(string $middleware): self
    {
        // 
        return $this;
    }
}