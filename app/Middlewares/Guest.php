<?php

namespace App\Middlewares;

use App\Supports\Auth;
use Bhitti\Http\Middleware\MiddlewareInterface;
use Bhitti\Http\Response;

class Guest implements MiddlewareInterface
{

    public function handle(): ?Response
    {
        if (Auth::check()) {
            return response()->redirect('/');
        }

        return null;
    }
}
