<?php

declare(strict_types=1);

namespace Bhitti\Database\Migration\Grammar;

use Bhitti\Database\Migration\ColumnDefinition;
use Bhitti\Database\Migration\IndexDefinition;

final class MySqlGrammar extends Grammar
{
    protected function quoteCharacter(): string
    {
        return '`';
    }

    protected function columnType(ColumnDefinition $column): string
    {
        return match ($column->type()) {
            'string' => 'VARCHAR(' . $column->length() . ')',
            'char' => 'CHAR(' . $column->length() . ')',
            'text' => 'TEXT',
            'mediumText' => 'MEDIUMTEXT',
            'longText' => 'LONGTEXT',
            'tinyInteger' => 'TINYINT',
            'smallInteger' => 'SMALLINT',
            'integer' => 'INT',
            'bigInteger' => 'BIGINT',
            'decimal' => 'DECIMAL(' . $column->precision() . ', ' . $column->scale() . ')',
            'float' => 'DOUBLE',
            'boolean' => 'TINYINT(1)',
            'date' => 'DATE',
            'dateTime' => 'DATETIME',
            'timestamp' => 'TIMESTAMP',
            'json' => 'JSON',
            'uuid' => 'CHAR(36)',
            'binary' => 'BLOB',
        };
    }

    protected function supportsUnsigned(): bool
    {
        return true;
    }

    protected function autoIncrementClause(ColumnDefinition $column, bool $creating): string
    {
        return ' AUTO_INCREMENT';
    }

    protected function createTableSql(string $table, array $definitions): string
    {
        return parent::createTableSql($table, $definitions) . ' ENGINE=InnoDB';
    }

    protected function compileDropIndex(string $table, string $index): string
    {
        return 'DROP INDEX ' . $this->wrap($index) . ' ON ' . $this->wrap($table);
    }

    protected function compileDropForeign(string $table, string $foreignKey): string
    {
        return 'ALTER TABLE ' . $this->wrap($table)
            . ' DROP FOREIGN KEY ' . $this->wrap($foreignKey);
    }

    protected function compileAddPrimary(string $table, IndexDefinition $index): string
    {
        return 'ALTER TABLE ' . $this->wrap($table)
            . ' ADD PRIMARY KEY (' . $this->columnList($index->columns) . ')';
    }
}
