<?php

declare(strict_types=1);

namespace App\Middlewares;

use App\Supports\Auth;
use Bhitti\Http\Middleware\MiddlewareInterface;
use Bhitti\Http\Response;

final class BearerAuth implements MiddlewareInterface
{
    public function handle(): ?Response
    {
        $token = request()->bearerToken();

        if ($token === null) {
            return response()
                ->header('WWW-Authenticate', 'Bearer')
                ->json(['error' => 'Unauthorized',], 401);
        }

        $user = $this->resolveUser($token);

        if ($user === null) {
            return response()
                ->header('WWW-Authenticate', 'Bearer')
                ->json(['error' => 'Invalid token'], 401);
        }

        Auth::once((int) $user->id, $user);
        return null;
    }

    private function resolveUser(string $token): ?object
    {
        $hashed = hash('sha256', $token);

        return db()
            ->table('api_tokens AS tokens')
            ->join('users AS users', 'users.id', '=', 'tokens.user_id')
            ->select('users.id', 'users.name', 'users.email', 'users.username')
            ->where('tokens.token',$hashed)
            ->where('tokens.expires_at', '>', date('Y-m-d H:i:s'))
            ->first();
    }
}