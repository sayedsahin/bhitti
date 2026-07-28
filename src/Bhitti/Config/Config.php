<?php

declare(strict_types=1);

namespace Bhitti\Config;

final class Config
{
    private static array $items = [];

    private static array $resolved = [];


    private static array $missing = [];

    public static function load(array $items): void
    {
        self::$items = $items;

        self::clearMemo();
    }

    public static function all(): array
    {
        return self::$items;
    }

    public static function has(string $key): bool
    {
        $value = null;

        return self::resolve($key, $value);
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $resolved = null;

        if (self::resolve($key, $resolved)) {
            return $resolved;
        }

        return value($default);
    }

    public static function set(string $key, mixed $value): void
    {
        self::write($key, $value);

        self::clearMemo();
    }

    public static function setMany(array $values): void
    {
        foreach ($values as $key => $value) {
            self::write((string) $key, $value);
        }

        self::clearMemo();
    }

    private static function resolve(string $key, mixed &$result): bool
    {

        if (array_key_exists($key,self::$resolved)) {
            $result = self::$resolved[$key];
            return true;
        }


        if (isset(self::$missing[$key])) {
            return false;
        }

        $value = self::$items;

        foreach (explode('.', $key) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                self::$missing[$key] = true;

                return false;
            }

            $value = $value[$segment];
        }

        self::$resolved[$key] = $value;
        $result = $value;

        return true;
    }

    private static function write(string $key, mixed $value): void
    {
        $segments = explode('.', $key);
        $items = &self::$items;

        foreach ($segments as $segment) {
            if (!isset($items[$segment]) || !is_array($items[$segment])) {
                $items[$segment] = [];
            }

            $items = &$items[$segment];
        }

        $items = $value;
    }

    private static function clearMemo(): void
    {
        self::$resolved = [];
        self::$missing = [];
    }
}
