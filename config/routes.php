<?php

use App\Controllers\AuthController;
use App\Controllers\ApiAuthController;
use App\Controllers\WelcomeController;
use App\Middlewares\Authenticated;
use App\Middlewares\BearerAuth;
use App\Middlewares\Guest;

/**
 * @var FastRoute\RouteCollector $route
 */


/*
|-----------------------------------------------------------
| Web Routes
|-----------------------------------------------------------
*/
$route->get('/', [WelcomeController::class, 'index']);

/* Session Based Authentication */
$route->get('/login', [AuthController::class, 'login', [Guest::class]]);
$route->post('/login', [AuthController::class, 'loginProcess', [Guest::class]]);
$route->get('/register', [AuthController::class, 'registration', [Guest::class]]);
$route->post('/register', [AuthController::class, 'registrationProcess', [Guest::class]]);
$route->post('/logout', [AuthController::class, 'logout', [Authenticated::class]]);


/*
|-----------------------------------------------------------
| API Routes
|-----------------------------------------------------------
*/
$route->addGroup('/api', function () use ($route) {

    $route->get('/welcome', [WelcomeController::class, 'apiIndex']);

    /* API Based Authentication */
    $route->addGroup('/auth', function () use ($route) {
        $route->post('/login', [ApiAuthController::class, 'login']);
        $route->post('/register', [ApiAuthController::class, 'register']);
        $route->post('/forgot', [ApiAuthController::class, 'forgot']);
        $route->get('/verify/{token}', [ApiAuthController::class, 'verify']);
        $route->post('/logout', [ApiAuthController::class, 'logout', [BearerAuth::class]]);
        $route->get('/profile', [ApiAuthController::class, 'profile', [BearerAuth::class]]);
    });

});