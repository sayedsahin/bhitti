<?php

declare(strict_types=1);

namespace Bhitti\Routing;

use Bhitti\Http\Request;
use FastRoute\Dispatcher;
use FastRoute\RouteCollector as FastRouteCollector;

final class Router
{
    private Dispatcher $dispatcher;

    public function __construct()
    {
        $this->dispatcher = \FastRoute\cachedDispatcher(
            static function (FastRouteCollector $route): void {
                require ROOT_PATH . '/config/routes.php';
            },
            [
                'routeCollector' => RouteCollector::class,
                'cacheFile' => STORAGE_PATH . '/cache/route.cache.php',
                'cacheDisabled' => (bool) config('app.debug', false),
            ]
        );
    }

    public function dispatch(Request $request): array
    {
        return $this->dispatcher->dispatch(
            $request->method(),
            $request->path()
        );
    }
}