<?php

declare(strict_types=1);

namespace Bhitti\Http\Middleware;

use Bhitti\Http\Response;

interface MiddlewareInterface
{
    public function handle(): ?Response;
}
