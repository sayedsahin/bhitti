<?php

declare(strict_types=1);

namespace Bhitti\Database\Migration\Grammar;

use InvalidArgumentException;

final class GrammarFactory
{
    public static function make(string $driver): Grammar
    {
        return match ($driver) {
            'mysql' => new MySqlGrammar(),
            'pgsql' => new PostgresGrammar(),
            'sqlite' => new SQLiteGrammar(),
            default => throw new InvalidArgumentException("Unsupported migration driver: {$driver}"),
        };
    }
}
