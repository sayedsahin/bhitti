<?php

declare(strict_types=1);

namespace Bhitti\Database\Migration\Grammar;

use Bhitti\Database\Migration\Blueprint;
use Bhitti\Database\Migration\ColumnDefinition;
use Bhitti\Database\Migration\Expression;
use Bhitti\Database\Migration\ForeignKeyDefinition;
use Bhitti\Database\Migration\IndexDefinition;
use InvalidArgumentException;
use RuntimeException;

abstract class Grammar
{
    abstract protected function quoteCharacter(): string;

    abstract protected function columnType(ColumnDefinition $column): string;

    /** @return array<int, string> */
    public function compileCreate(Blueprint $blueprint): array
    {
        $columns = $blueprint->columns();

        if ($columns === []) {
            throw new RuntimeException("Cannot create table [{$blueprint->table()}] without columns.");
        }

        $indexes = $blueprint->indexes();
        $foreignKeys = $blueprint->foreignKeys();

        $this->validateCreateBlueprint($blueprint, $indexes, $foreignKeys);

        $definitions = [];
        $primaryColumns = [];

        foreach ($columns as $column) {
            $definitions[] = $this->compileColumn($column, true);

            if ($column->isPrimary() && !$this->isInlineAutoIncrementPrimary($column)) {
                $primaryColumns[] = $column->name();
            }
        }

        foreach ($indexes as $index) {
            if ($index->type === 'primary') {
                $primaryColumns = $index->columns;
                break;
            }
        }

        if ($primaryColumns !== []) {
            $definitions[] = 'PRIMARY KEY (' . $this->columnList($primaryColumns) . ')';
        }

        foreach ($foreignKeys as $foreignKey) {
            $this->validateSetNullColumns($blueprint, $foreignKey);
            $definitions[] = $this->compileForeignKey($foreignKey);
        }

        $statements = [
            $this->createTableSql($blueprint->table(), $definitions),
        ];

        foreach ($indexes as $index) {
            if ($index->type !== 'primary') {
                $statements[] = $this->compileCreateIndex($blueprint->table(), $index);
            }
        }

        return $statements;
    }

    /** @return array<int, string> */
    public function compileAlter(Blueprint $blueprint): array
    {
        $statements = [];
        $table = $this->wrap($blueprint->table());

        foreach ($blueprint->dropForeignKeys() as $foreignKey) {
            $statements[] = $this->compileDropForeign($blueprint->table(), $foreignKey);
        }

        foreach ($blueprint->dropIndexes() as $index) {
            $statements[] = $this->compileDropIndex($blueprint->table(), $index);
        }

        foreach ($blueprint->dropColumns() as $column) {
            $statements[] = "ALTER TABLE {$table} DROP COLUMN {$this->wrap($column)}";
        }

        foreach ($blueprint->renameColumns() as $from => $to) {
            $statements[] = "ALTER TABLE {$table} RENAME COLUMN {$this->wrap($from)} TO {$this->wrap($to)}";
        }

        foreach ($blueprint->columns() as $column) {
            if ($column->isPrimary() || $column->isAutoIncrement()) {
                throw new RuntimeException(
                    'Adding a primary or auto-increment column to an existing table is not portable. Use a raw statement.'
                );
            }

            $statements[] = "ALTER TABLE {$table} ADD COLUMN " . $this->compileColumn($column, false);
        }

        foreach ($blueprint->indexes() as $index) {
            if ($index->type === 'primary') {
                $statements[] = $this->compileAddPrimary($blueprint->table(), $index);
                continue;
            }

            $statements[] = $this->compileCreateIndex($blueprint->table(), $index);
        }

        foreach ($blueprint->foreignKeys() as $foreignKey) {
            $this->validateSetNullColumns($blueprint, $foreignKey);
            $statements[] = $this->compileAddForeign($blueprint->table(), $foreignKey);
        }

        return $statements;
    }

    public function compileDrop(string $table): string
    {
        return 'DROP TABLE ' . $this->wrap($table);
    }

    public function compileDropIfExists(string $table): string
    {
        return 'DROP TABLE IF EXISTS ' . $this->wrap($table);
    }

    public function compileRename(string $from, string $to): string
    {
        return 'ALTER TABLE ' . $this->wrap($from) . ' RENAME TO ' . $this->wrap($to);
    }

    public function wrap(string $identifier): string
    {
        $quote = $this->quoteCharacter();
        $parts = explode('.', $identifier);

        foreach ($parts as &$part) {
            if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $part)) {
                throw new InvalidArgumentException("Invalid SQL identifier: {$identifier}");
            }

            $part = $quote . $part . $quote;
        }

        return implode('.', $parts);
    }

    protected function createTableSql(string $table, array $definitions): string
    {
        return 'CREATE TABLE ' . $this->wrap($table)
            . ' (' . implode(', ', $definitions) . ')';
    }

    protected function compileColumn(ColumnDefinition $column, bool $creating): string
    {
        $this->validateColumn($column, $creating);

        $sql = $this->wrap($column->name()) . ' ' . $this->columnType($column);

        if ($column->isUnsigned() && $this->supportsUnsigned()) {
            $sql .= ' UNSIGNED';
        }

        if ($column->isAutoIncrement()) {
            $sql .= $this->autoIncrementClause($column, $creating);
        }

        if (!$column->isNullable()) {
            $sql .= ' NOT NULL';
        }

        if ($column->hasDefault()) {
            $sql .= ' DEFAULT ' . $this->defaultValue($column->defaultValue());
        }

        if ($this->isInlineAutoIncrementPrimary($column)) {
            $sql .= $this->inlinePrimaryClause($column);
        }

        return $sql;
    }

    protected function validateColumn(ColumnDefinition $column, bool $creating): void
    {
        if ($column->isAutoIncrement()
            && !in_array($column->type(), ['tinyInteger', 'smallInteger', 'integer', 'bigInteger'], true)) {
            throw new RuntimeException(
                "Auto-increment column [{$column->name()}] must use an integer type."
            );
        }

        if ($column->isAutoIncrement() && !$column->isPrimary()) {
            throw new RuntimeException(
                "Auto-increment column [{$column->name()}] must also be a primary key."
            );
        }

        if (!$creating && $column->isAutoIncrement()) {
            throw new RuntimeException(
                'Adding an auto-increment column to an existing table is not portable. Use a raw statement.'
            );
        }

        if (($column->isPrimary() || $column->isAutoIncrement()) && $column->isNullable()) {
            throw new RuntimeException(
                "Primary or auto-increment column [{$column->name()}] cannot be nullable."
            );
        }

        if ($column->isAutoIncrement() && $column->hasDefault()) {
            throw new RuntimeException(
                "Auto-increment column [{$column->name()}] cannot define a default value."
            );
        }

        if ($column->hasDefault() && $column->defaultValue() === null && !$column->isNullable()) {
            throw new RuntimeException(
                "Column [{$column->name()}] cannot use a NULL default unless it is nullable."
            );
        }
    }

    protected function supportsUnsigned(): bool
    {
        return false;
    }

    protected function autoIncrementClause(ColumnDefinition $column, bool $creating): string
    {
        return '';
    }

    protected function isInlineAutoIncrementPrimary(ColumnDefinition $column): bool
    {
        return false;
    }

    protected function inlinePrimaryClause(ColumnDefinition $column): string
    {
        return ' PRIMARY KEY';
    }

    protected function defaultValue(mixed $value): string
    {
        if ($value instanceof Expression) {
            return $value->value;
        }

        if ($value === null) {
            return 'NULL';
        }

        if (is_bool($value)) {
            return $this->booleanLiteral($value);
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return "'" . str_replace("'", "''", (string) $value) . "'";
    }

    protected function booleanLiteral(bool $value): string
    {
        return $value ? '1' : '0';
    }

    protected function compileForeignKey(ForeignKeyDefinition $foreignKey): string
    {
        if (count($foreignKey->columns()) !== count($foreignKey->referencedColumns())) {
            throw new RuntimeException(
                "Foreign key [{$foreignKey->name()}] must reference the same number of columns."
            );
        }

        $sql = 'CONSTRAINT ' . $this->wrap($foreignKey->name())
            . ' FOREIGN KEY (' . $this->columnList($foreignKey->columns()) . ')'
            . ' REFERENCES ' . $this->wrap($foreignKey->referencedTable())
            . ' (' . $this->columnList($foreignKey->referencedColumns()) . ')';

        if ($foreignKey->deleteAction() !== null) {
            $sql .= ' ON DELETE ' . $foreignKey->deleteAction();
        }

        if ($foreignKey->updateAction() !== null) {
            $sql .= ' ON UPDATE ' . $foreignKey->updateAction();
        }

        return $sql;
    }

    protected function compileCreateIndex(string $table, IndexDefinition $index): string
    {
        $unique = $index->type === 'unique' ? 'UNIQUE ' : '';

        return "CREATE {$unique}INDEX {$this->wrap($index->name)}"
            . ' ON ' . $this->wrap($table)
            . ' (' . $this->columnList($index->columns) . ')';
    }

    protected function compileAddPrimary(string $table, IndexDefinition $index): string
    {
        return 'ALTER TABLE ' . $this->wrap($table)
            . ' ADD CONSTRAINT ' . $this->wrap($index->name)
            . ' PRIMARY KEY (' . $this->columnList($index->columns) . ')';
    }

    protected function compileAddForeign(string $table, ForeignKeyDefinition $foreignKey): string
    {
        return 'ALTER TABLE ' . $this->wrap($table)
            . ' ADD ' . $this->compileForeignKey($foreignKey);
    }

    protected function compileDropIndex(string $table, string $index): string
    {
        return 'DROP INDEX ' . $this->wrap($index);
    }

    protected function compileDropForeign(string $table, string $foreignKey): string
    {
        return 'ALTER TABLE ' . $this->wrap($table)
            . ' DROP CONSTRAINT ' . $this->wrap($foreignKey);
    }

    /**
     * @param array<int, IndexDefinition> $indexes
     * @param array<int, ForeignKeyDefinition> $foreignKeys
     */
    private function validateCreateBlueprint(
        Blueprint $blueprint,
        array $indexes,
        array $foreignKeys
    ): void {
        $columnNames = [];
        $columnPrimary = [];
        $autoIncrement = [];

        foreach ($blueprint->columns() as $column) {
            $columnNames[$column->name()] = true;

            if ($column->isPrimary()) {
                $columnPrimary[] = $column->name();
            }

            if ($column->isAutoIncrement()) {
                $autoIncrement[] = $column->name();
            }
        }

        $tablePrimary = array_values(array_filter(
            $indexes,
            static fn (IndexDefinition $index): bool => $index->type === 'primary'
        ));

        if (count($tablePrimary) > 1) {
            throw new RuntimeException(
                "Table [{$blueprint->table()}] defines more than one table-level primary key."
            );
        }

        if (count($columnPrimary) > 1) {
            throw new RuntimeException(
                'Composite primary keys must be defined with $table->primary([...]), not multiple column-level primary() modifiers.'
            );
        }

        if ($tablePrimary !== [] && $columnPrimary !== []) {
            throw new RuntimeException(
                'Column-level primary() and table-level primary([...]) cannot be used together.'
            );
        }

        if (count($autoIncrement) > 1) {
            throw new RuntimeException('A table can define only one auto-increment column.');
        }

        foreach ($indexes as $index) {
            $this->assertColumnsExist(
                $blueprint,
                $columnNames,
                $index->columns,
                "index [{$index->name}]"
            );
        }

        foreach ($foreignKeys as $foreignKey) {
            $this->assertColumnsExist(
                $blueprint,
                $columnNames,
                $foreignKey->columns(),
                "foreign key [{$foreignKey->name()}]"
            );
        }
    }

    /**
     * @param array<string, bool> $columnNames
     * @param array<int, string> $columns
     */
    private function assertColumnsExist(
        Blueprint $blueprint,
        array $columnNames,
        array $columns,
        string $definition
    ): void {
        foreach ($columns as $column) {
            if (!isset($columnNames[$column])) {
                throw new RuntimeException(
                    ucfirst($definition) . " references missing column [{$column}] on table [{$blueprint->table()}]."
                );
            }
        }
    }

    private function validateSetNullColumns(
        Blueprint $blueprint,
        ForeignKeyDefinition $foreignKey
    ): void {
        if ($foreignKey->deleteAction() !== 'SET NULL'
            && $foreignKey->updateAction() !== 'SET NULL') {
            return;
        }

        $columns = [];

        foreach ($blueprint->columns() as $column) {
            $columns[$column->name()] = $column;
        }

        foreach ($foreignKey->columns() as $name) {
            if (isset($columns[$name]) && !$columns[$name]->isNullable()) {
                throw new RuntimeException(
                    "Foreign key [{$foreignKey->name()}] uses SET NULL but column [{$name}] is not nullable."
                );
            }
        }
    }

    /** @param array<int, string> $columns */
    protected function columnList(array $columns): string
    {
        return implode(', ', array_map($this->wrap(...), $columns));
    }
}
