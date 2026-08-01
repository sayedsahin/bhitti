<?php

declare(strict_types=1);

namespace Bhitti\Core;

use Bhitti\Http\Middleware\MiddlewareKernel;
use Bhitti\Http\Request;
use Bhitti\Http\Response;
use Bhitti\Routing\RouteDispatcher;
use Bhitti\Routing\Router;
use Bhitti\Session\Session;
use Bhitti\Session\SessionManager;

final class Kernel
{
    public function __construct(
        private MiddlewareKernel $middleware,
        private Router $router,
        private RouteDispatcher $dispatcher
    ) {
    }

    public function handle(Request $request): void
    {
        $isApi = $request->isApi();

        if (!$isApi) {
            SessionManager::configure(
                (array) config('session', [])
            );
        }

        $config = (array) config('middleware', []);

        $this->middleware->web($config['web'] ?? []);
        $this->middleware->api($config['api'] ?? []);

        $middlewareResponse = $this->middleware->handleGlobal($isApi);

        if ($middlewareResponse instanceof Response) {

            $middlewareResponse->send();
            return;
        }

        $routeInfo = $this->router->dispatch($request);

        $this->dispatcher->dispatch($routeInfo, $isApi);
    }
}