<?php
namespace Bhitti\Session;

final class Cookie
{
    public static function set(
        string $name,
        string $value,
        int $ttl,
        string $samesite = 'Lax'
    ): void {
        setcookie($name, $value, [
            'expires'  => time() + $ttl,
            'path'     => '/',
            'secure'   => request()->isSecure(),
            'httponly' => true,
            'samesite' => $samesite,
        ]);
    }

    public static function get(string $name): ?string
    {
        return $_COOKIE[$name] ?? null;
    }

    public static function forget(string $name): void
    {
        setcookie($name, '', time() - 3600, '/');
    }
}
