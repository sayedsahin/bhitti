<?php

declare(strict_types=1);

namespace Bhitti\Database\Migration\Grammar;

use Bhitti\Database\Migration\Blueprint;
use Bhitti\Database\Migration\ColumnDefinition;
use Bhitti\Database\Migration\ForeignKeyDefinition;
use Bhitti\Database\Migration\IndexDefinition;
use RuntimeException;

final class SQLiteGrammar extends Grammar
{
    protected function quoteCharacter(): string
    {
        return '"';
    }

    protected function columnType(ColumnDefinition $column): string
    {
        if ($column->isAutoIncrement()) {
            return 'INTEGER';
        }

        return match ($column->type()) {
            'string' => 'VARCHAR(' . $column->length() . ')',
            'char' => 'CHAR(' . $column->length() . ')',
            'text', 'mediumText', 'longText' => 'TEXT',
            'tinyInteger', 'smallInteger', 'integer', 'bigInteger', 'boolean' => 'INTEGER',
            'decimal' => 'NUMERIC(' . $column->precision() . ', ' . $column->scale() . ')',
            'float' => 'REAL',
            'date', 'dateTime', 'timestamp', 'uuid', 'json' => 'TEXT',
            'binary' => 'BLOB',
        };
    }


    protected function compileColumn(ColumnDefinition $column, bool $creating): string
    {
        if ($column->isAutoIncrement()) {
            $this->validateColumn($column, $creating);

            return $this->wrap($column->name()) . ' INTEGER PRIMARY KEY AUTOINCREMENT';
        }

        return parent::compileColumn($column, $creating);
    }

    protected function isInlineAutoIncrementPrimary(ColumnDefinition $column): bool
    {
        return $column->isAutoIncrement() && $column->isPrimary();
    }

    protected function inlinePrimaryClause(ColumnDefinition $column): string
    {
        return ' PRIMARY KEY AUTOINCREMENT';
    }

    /** @return array<int, string> */
    public function compileAlter(Blueprint $blueprint): array
    {
        if ($blueprint->foreignKeys() !== [] || $blueprint->dropForeignKeys() !== []) {
            throw new RuntimeException(
                'SQLite cannot add or drop foreign keys on an existing table. Rebuild the table or use a raw migration.'
            );
        }

        foreach ($blueprint->indexes() as $index) {
            if ($index->type === 'primary') {
                throw new RuntimeException(
                    'SQLite cannot add a primary key to an existing table. Rebuild the table.'
                );
            }
        }

        return parent::compileAlter($blueprint);
    }

    protected function compileAddPrimary(string $table, IndexDefinition $index): string
    {
        throw new RuntimeException('SQLite cannot add a primary key to an existing table.');
    }

    protected function compileAddForeign(string $table, ForeignKeyDefinition $foreignKey): string
    {
        throw new RuntimeException('SQLite cannot add a foreign key to an existing table.');
    }
}
