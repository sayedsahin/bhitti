<?php

declare(strict_types=1);

namespace App\Supports;

use Bhitti\Http\RequestContext;
use Bhitti\Session\Cookie;
use Bhitti\Session\Session;
use RuntimeException;

final class Auth
{
    private static $resolver;

    public static function setResolver(callable $resolver): void
    {
        self::$resolver = $resolver;
    }

    private static function resolveUser(int $id): mixed
    {
        if (! self::$resolver) {
            throw new RuntimeException('User resolver not set.');
        }

        return call_user_func(self::$resolver, $id);
    }

    /* ===============================
       AUTH STATE (request-scoped)
    =============================== */

    private static function setUser(mixed $user): void
    {
        RequestContext::set('auth.user', $user);
    }

    private static function getUser(): mixed
    {
        return RequestContext::get('auth.user');
    }

    private static function setId(?int $id): void
    {
        RequestContext::set('auth.id', $id);
    }

    private static function getId(): ?int
    {
        return RequestContext::get('auth.id');
    }

    /* ===============================
       CORE METHODS
    =============================== */

    public static function login(int $userId): void
    {
        Session::regenerate();
        Session::set('auth_user_id', $userId);

        // clear request cache
        self::setUser(null);
        self::setId($userId);
    }

    public static function once(int $userId, mixed $user = null): void
    {
        self::setId($userId);
        self::setUser($user);
    }

    public static function logout(): void
    {
        Session::destroy();
        Cookie::forget('remember_token');

        self::setUser(null);
        self::setId(null);
    }

    public static function id(): ?int
    {
        // 1️ check request cache
        $cached = self::getId();
        if ($cached !== null) {
            return $cached;
        }

        // 2️ fallback session
        $id = Session::get('auth_user_id');

        if ($id !== null) {
            self::setId($id);
        }

        return $id;
    }

    public static function check(): bool
    {
        return self::id() !== null;
    }

    public static function user(): mixed
    {
        // 1️ cached user
        $user = self::getUser();
        if ($user !== null) {
            return $user;
        }

        // 2️ resolve id
        $id = self::id();
        if (! $id) {
            return null;
        }

        // 3️ resolve user
        $user = self::resolveUser($id);

        self::setUser($user);

        return $user;
    }

    public static function viaRemember(): bool
    {
        return RequestContext::get('auth.via_remember', false);
    }

    public static function setViaRemember(bool $state): void
    {
        RequestContext::set('auth.via_remember', $state);
    }
}
