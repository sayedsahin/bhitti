<?php

declare(strict_types=1);


/*
|--------------------------------------------------------------------------
| Bootstrap Paths & Autoload
|--------------------------------------------------------------------------
*/
require_once __DIR__ . '/../config/path.php';
require_once ROOT_PATH . '/vendor/autoload.php';

/*
|--------------------------------------------------------------------------
| Load Config
|--------------------------------------------------------------------------
| If storage/cache/config.php exists, config loads from cache.
| If not, framework loads .env, builds config cache, then loads config.
*/
require ROOT_PATH . '/bootstrap/config.php';

/*
|--------------------------------------------------------------------------
| Detect Request Type (API or WEB)
|--------------------------------------------------------------------------
*/
$isApi = is_api_request();


\Bhitti\Exception\ExceptionHandler::register(
    (bool) config('app.debug'),
    $isApi
);


if (!$isApi) {
    require ROOT_PATH . '/bootstrap/session.php';
}

/*
|--------------------------------------------------------------------------
| Cache Setup
|--------------------------------------------------------------------------
*/
require ROOT_PATH . '/bootstrap/cache.php';


/*
|--------------------------------------------------------------------------
| Auth Setup
|--------------------------------------------------------------------------
*/
require ROOT_PATH . '/bootstrap/auth.php';

/*
|--------------------------------------------------------------------------
| Container Setup
|--------------------------------------------------------------------------
*/
require ROOT_PATH . '/bootstrap/container.php';


/*
|--------------------------------------------------------------------------
| Middleware Kernel (Headers, Auth, CSRF, etc.)
|--------------------------------------------------------------------------
*/
$kernel = new \Bhitti\Http\Middleware\MiddlewareKernel();
$config = config('middleware');

$kernel->web($config['web']);
$kernel->api($config['api']);

$kernel->run($isApi);

/*
|--------------------------------------------------------------------------
| Route Dispatch
|--------------------------------------------------------------------------
*/
require ROOT_PATH . '/bootstrap/router.php';



// request remove after ending
// Bhitti\Http\RequestContext::clear();
