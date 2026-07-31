<?php

declare(strict_types=1);

namespace App\Controllers;

use Bhitti\Http\Middleware\MiddlewareKernel;
use Bhitti\Http\Response;

abstract class Controller
{
    public function __construct(private MiddlewareKernel $middleware) {

    }
    /**
     * Execute one middleware immediately.
     */
    protected function middleware(string|array $middleware): void
    {
        $response = $this->middleware->handle([
            $middleware,
        ]);

        if ($response instanceof Response) {
            $response->send();
            exit;
        }
    }
}