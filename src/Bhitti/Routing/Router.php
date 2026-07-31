<?php

declare(strict_types=1);

namespace Bhitti\Routing;

use Bhitti\Http\Request;
use FastRoute\Dispatcher;
use FastRoute\RouteCollector;

final class Router
{
    private Dispatcher $dispatcher;

    public function __construct() {
        $this->dispatcher = \FastRoute\cachedDispatcher(
            static function (RouteCollector $route): void {
                require ROOT_PATH . '/config/routes.php';
            },
            [
                'cacheFile' => STORAGE_PATH . '/cache/route.cache',
                'cacheDisabled' => config('app.debug'),
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