<?php

use App\Models\DB;
use App\Supports\Role;
use App\Systems\Cache\Cache;
use App\Systems\Database;
use App\Systems\Session\Session;

if (!function_exists('cache')) {
    function cache(): Cache
    {
        static $cache;

        return $cache ??= new Cache();
    }
}

if (!function_exists('db')) {
    function db(?string $connection = null): DB
    {
        global $container;

        return new DB(
            $container->make(Database::class),
            $connection
        );
    }
}

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

/**
 * @return Session
 */

if (!function_exists('session')) {
    function session()
    {
        static $proxy = null;

        if ($proxy === null) {
            $proxy = new class {
                public function __call($method, $args)
                {
                    return Session::$method(...$args);
                }
            };
        }

        return $proxy;
    }
}