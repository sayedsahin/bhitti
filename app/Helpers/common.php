<?php

use App\Supports\Auth;
use App\Supports\Role;

if (!function_exists('role')) {
    function role(string $role): bool
    {
        return Role::has($role);
    }
}

if (!function_exists('roles')) {
    function roles(array $roles): bool
    {
        return Role::any($roles);
    }
}

if (!function_exists('auth')) {
    function auth(): Auth
    {
        global $container;

        return $container->make(Auth::class);
    }
}