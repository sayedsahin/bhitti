<?php

declare(strict_types=1);

namespace Bhitti\Http;

final class RequestContext
{
    private static array $store = [];

    public static function set(string $key, mixed $value): void
    {
        self::$store[$key] = $value;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return self::$store[$key] ?? $default;
    }

    public static function clear(): void
    {
        self::$store = [];
    }
}
