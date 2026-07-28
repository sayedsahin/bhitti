<?php

namespace App\Middlewares;

use Bhitti\Http\Middleware\MiddlewareInterface;
use Bhitti\Http\Response;
use Bhitti\Session\Session;

class Csrf implements MiddlewareInterface
{
    public function handle(): ?Response
    {
        $method = strtoupper(request()->method());

        if (!in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return null;
        }

        $sessionToken = Session::get('_csrf');

        $token = request()->post('_csrf')
            ?? request()->header('x-csrf-token')
            ?? request()->json('_csrf', '');

        if (
            !is_string($sessionToken)
            || !is_string($token)
            || !hash_equals($sessionToken, $token)
        ) {
            return response()->html('CSRF token mismatch', 419);
        }

        return null;
    }
}

