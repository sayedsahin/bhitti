<?php

namespace App\Middlewares;

use Bhitti\Http\Middleware\MiddlewareInterface;
use Bhitti\Http\Response;

class WebHeaders implements MiddlewareInterface
{
    public function handle(): ?Response
    {
        header('X-Frame-Options: SAMEORIGIN');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: strict-origin-when-cross-origin');

        header(
            "Content-Security-Policy: " .
                "default-src 'self'; " .
                "script-src 'self' https://cdn.jsdelivr.net; " .
                "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; " .
                "img-src 'self' data:; " .
                "connect-src 'self' https://cdn.jsdelivr.net;"
        );

        return null;
    }
}
