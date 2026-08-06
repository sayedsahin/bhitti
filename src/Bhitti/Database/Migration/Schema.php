<?php

declare(strict_types=1);

namespace Bhitti\Database\Migration;

use LogicException;
use PDO;

final class Schema
{
    private static ?SchemaManager $manager = null;

    public static function setManager(SchemaManager $manager): void
    {
        self::$manager = $manager;
    }

    public static function clearManager(): void
    {
        self::$manager = null;
    }

    public static function create(string $table, callable $callback): void
    {
        self::manager()->create($table, $callback);
    }

    public static function createIfNotExists(string $table, callable $callback): void
    {
        self::manager()->createIfNotExists($table, $callback);
    }

    public static function table(string $table, callable $callback): void
    {
        self::manager()->table($table, $callback);
    }

    public static function drop(string $table): void
    {
        self::manager()->drop($table);
    }

    public static function dropIfExists(string $table): void
    {
        self::manager()->dropIfExists($table);
    }

    public static function rename(string $from, string $to): void
    {
        self::manager()->rename($from, $to);
    }

    public static function hasTable(string $table): bool
    {
        return self::manager()->hasTable($table);
    }

    public static function hasColumn(string $table, string $column): bool
    {
        return self::manager()->hasColumn($table, $column);
    }

    public static function statement(string $sql, array $bindings = []): int
    {
        return self::manager()->statement($sql, $bindings);
    }

    public static function pdo(): PDO
    {
        return self::manager()->pdo();
    }

    public static function driver(): string
    {
        return self::manager()->driver();
    }

    public static function connectionName(): ?string
    {
        return self::manager()->connectionName();
    }

    private static function manager(): SchemaManager
    {
        return self::$manager
            ?? throw new LogicException('Schema has not been initialized by the migration runner.');
    }
}
