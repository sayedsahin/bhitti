<?php

namespace App\Middlewares;

use App\Supports\Auth;
use App\Supports\Role;
use Bhitti\Http\Middleware\MiddlewareInterface;
use Bhitti\Http\Response;

class RoleMiddleware implements MiddlewareInterface
{
    private array $roles;

    public function __construct(array $roles)
    {
        $this->roles = $roles;
    }

    public function handle(): ?Response
    {
        $isApi = is_api_request();
        if (!Auth::check()) {
            if ($isApi) {
                return response()->json([
                    'error' => 'Unauthorized',
                ], 401);
            }

            return response()->html('Unauthorized', 401);
        }

        if (!Role::any($this->roles)) {
            if ($isApi) {
                return response()->json([
                    'error' => 'Forbidden',
                ], 403);
            }

            return response()->html('Forbidden', 403);
        }

        return null;
    }
}