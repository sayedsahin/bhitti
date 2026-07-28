<?php
namespace App\Middlewares;

use Bhitti\Http\Middleware\MiddlewareInterface;
use Bhitti\Http\Response;
use Bhitti\Session\Session;

final class SessionStart implements MiddlewareInterface
{
    public function handle(): ?Response
    {
        Session::start();
        return null;
    }
}
