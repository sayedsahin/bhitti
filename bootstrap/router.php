<?php

declare(strict_types=1);

/** @var bool $isApi */

$dispatcher = FastRoute\cachedDispatcher(function (FastRoute\RouteCollector $route) {
	require_once ROOT_PATH . '/config/routes.php';
}, [
	'cacheFile' => ROOT_PATH . '/storage/cache/route.cache', /* required */
	'cacheDisabled' => config('app.debug'),     /* optional, enabled by default */
]);

// Fetch method and URI from somewhere
$httpMethod =request()->method();
$uri = request()->path();

// Strip query string (?foo=bar) and decode URI
// if (false !== $pos = strpos($uri, '?')) {
// 	$uri = substr($uri, 0, $pos);
// }
// $uri = rawurldecode($uri);

$routeInfo = $dispatcher->dispatch($httpMethod, $uri);
switch ($routeInfo[0]) {
	case FastRoute\Dispatcher::NOT_FOUND:
		// $isApi come from public/index.php
		if ($isApi) {
            response()->json([
				'error' => 'Not Found',
			],404)->send();
            return;
        }
		http_response_code(404);
		echo 'Not Found';
		return;
	case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
		$allowedMethods = $routeInfo[1];
		$httpMethods = implode(', ', $allowedMethods);
		if ($isApi) {
            response()->json([
				'error' => 'Method Not Allowed',
			],405)->header('Allow', $httpMethods)->send();
            return;
        }

		header('Allow: ' . $httpMethods);
		http_response_code(405);
		echo 'Method Not Allowed ';
		return;
	case FastRoute\Dispatcher::FOUND:
		$handler = $routeInfo[1];
		$vars = $routeInfo[2];

		$middlewares = $handler[2] ?? [];

		$middlewareResponse = \Bhitti\Http\Middleware\MiddlewareKernel::handle($middlewares);

		if ($middlewareResponse instanceof \Bhitti\Http\Response) {
			$middlewareResponse->send();
			return;
		}

		global $container;
		$controller = $container->make($handler[0]);
		// call_user_func_array([$controller, $handler[1]], $vars);
		$action = $handler[1];
		$result = $controller->$action(...array_values($vars));

		// Handle Response objects
		if ($result instanceof \Bhitti\Http\Response) {
			$result->send();
		}
		break;
}
