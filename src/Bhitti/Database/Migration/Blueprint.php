<?php

declare(strict_types=1);

namespace Bhitti\Database\Migration;

use InvalidArgumentException;

final class Blueprint
{
    /** @var array<int, ColumnDefinition> */
    private array $columns = [];

    /** @var array<int, IndexDefinition> */
    private array $indexes = [];

    /** @var array<int, ForeignKeyDefinition> */
    private array $foreignKeys = [];

    /** @var array<int, string> */
    private array $dropColumns = [];

    /** @var array<string, string> */
    private array $renameColumns = [];

    /** @var array<int, string> */
    private array $dropIndexes = [];

    /** @var array<int, string> */
    private array $dropForeignKeys = [];

    public function __construct(
        private readonly string $table,
        private readonly bool $creating
    ) {
        $this->assertIdentifier($table);
    }

    public function id(string $name = 'id'): ColumnDefinition
    {
        return $this->bigInteger($name)
            ->unsigned()
            ->autoIncrement()
            ->primary();
    }

    public function increments(string $name = 'id'): ColumnDefinition
    {
        return $this->integer($name)
            ->unsigned()
            ->autoIncrement()
            ->primary();
    }

    public function bigIncrements(string $name = 'id'): ColumnDefinition
    {
        return $this->id($name);
    }

    public function foreignId(string $name): ForeignIdColumnDefinition
    {
        $column = new ForeignIdColumnDefinition($this, $name, 'bigInteger');
        $column->unsigned();

        return $this->addColumn($column);
    }

    public function string(string $name, int $length = 255): ColumnDefinition
    {
        if ($length < 1) {
            throw new InvalidArgumentException('String length must be greater than zero.');
        }

        return $this->column($name, 'string', $length);
    }

    public function char(string $name, int $length = 1): ColumnDefinition
    {
        if ($length < 1) {
            throw new InvalidArgumentException('Char length must be greater than zero.');
        }

        return $this->column($name, 'char', $length);
    }

    public function text(string $name): ColumnDefinition
    {
        return $this->column($name, 'text');
    }

    public function mediumText(string $name): ColumnDefinition
    {
        return $this->column($name, 'mediumText');
    }

    public function longText(string $name): ColumnDefinition
    {
        return $this->column($name, 'longText');
    }

    public function tinyInteger(string $name): ColumnDefinition
    {
        return $this->column($name, 'tinyInteger');
    }

    public function smallInteger(string $name): ColumnDefinition
    {
        return $this->column($name, 'smallInteger');
    }

    public function integer(string $name): ColumnDefinition
    {
        return $this->column($name, 'integer');
    }

    public function bigInteger(string $name): ColumnDefinition
    {
        return $this->column($name, 'bigInteger');
    }

    public function decimal(string $name, int $precision = 10, int $scale = 2): ColumnDefinition
    {
        if ($precision < 1 || $scale < 0 || $scale > $precision) {
            throw new InvalidArgumentException('Invalid decimal precision or scale.');
        }

        return $this->column($name, 'decimal', null, $precision, $scale);
    }

    public function float(string $name): ColumnDefinition
    {
        return $this->column($name, 'float');
    }

    public function boolean(string $name): ColumnDefinition
    {
        return $this->column($name, 'boolean');
    }

    public function date(string $name): ColumnDefinition
    {
        return $this->column($name, 'date');
    }

    public function dateTime(string $name): ColumnDefinition
    {
        return $this->column($name, 'dateTime');
    }

    public function timestamp(string $name): ColumnDefinition
    {
        return $this->column($name, 'timestamp');
    }

    public function json(string $name): ColumnDefinition
    {
        return $this->column($name, 'json');
    }

    public function uuid(string $name): ColumnDefinition
    {
        return $this->column($name, 'uuid');
    }

    public function binary(string $name): ColumnDefinition
    {
        return $this->column($name, 'binary');
    }

    public function timestamps(): void
    {
        $this->timestamp('created_at')->nullable();
        $this->timestamp('updated_at')->nullable();
    }

    public function primary(string|array $columns, ?string $name = null): void
    {
        $columns = $this->normalizeColumns($columns);
        $name ??= $this->indexName('primary', $columns);
        $this->indexes[] = new IndexDefinition('primary', $columns, $name);
    }

    public function unique(string|array $columns, ?string $name = null): void
    {
        $columns = $this->normalizeColumns($columns);
        $name ??= $this->indexName('unique', $columns);
        $this->indexes[] = new IndexDefinition('unique', $columns, $name);
    }

    public function index(string|array $columns, ?string $name = null): void
    {
        $columns = $this->normalizeColumns($columns);
        $name ??= $this->indexName('index', $columns);
        $this->indexes[] = new IndexDefinition('index', $columns, $name);
    }

    public function foreign(string|array $columns, ?string $name = null): ForeignKeyDefinition
    {
        $columns = $this->normalizeColumns($columns);
        $name ??= $this->indexName('foreign', $columns);
        $foreign = new ForeignKeyDefinition($this, $columns, $name);
        $this->foreignKeys[] = $foreign;

        return $foreign;
    }

    public function dropColumn(string|array $columns): void
    {
        foreach ($this->normalizeColumns($columns) as $column) {
            $this->dropColumns[] = $column;
        }
    }

    public function renameColumn(string $from, string $to): void
    {
        $this->assertIdentifier($from);
        $this->assertIdentifier($to);
        $this->renameColumns[$from] = $to;
    }

    public function dropIndex(string $name): void
    {
        $this->assertIdentifier($name);
        $this->dropIndexes[] = $name;
    }

    public function dropUnique(string $name): void
    {
        $this->dropIndex($name);
    }

    public function dropForeign(string $name): void
    {
        $this->assertIdentifier($name);
        $this->dropForeignKeys[] = $name;
    }

    public function table(): string
    {
        return $this->table;
    }

    public function isCreating(): bool
    {
        return $this->creating;
    }

    /** @return array<int, ColumnDefinition> */
    public function columns(): array
    {
        return $this->columns;
    }

    /**
     * Returns explicit indexes plus column-level unique/index modifiers.
     *
     * @return array<int, IndexDefinition>
     */
    public function indexes(): array
    {
        $indexes = $this->indexes;

        foreach ($this->columns as $column) {
            if ($column->uniqueName() !== null) {
                $indexes[] = new IndexDefinition('unique', [$column->name()], $column->uniqueName());
            }

            if ($column->indexName() !== null) {
                $indexes[] = new IndexDefinition('index', [$column->name()], $column->indexName());
            }
        }

        $seen = [];

        foreach ($indexes as $index) {
            if (isset($seen[$index->name])) {
                throw new InvalidArgumentException(
                    "Index [{$index->name}] is defined more than once on table [{$this->table}]."
                );
            }

            $seen[$index->name] = true;
        }

        return $indexes;
    }

    /** @return array<int, ForeignKeyDefinition> */
    public function foreignKeys(): array
    {
        $seen = [];

        foreach ($this->foreignKeys as $foreignKey) {
            if (isset($seen[$foreignKey->name()])) {
                throw new InvalidArgumentException(
                    "Foreign key [{$foreignKey->name()}] is defined more than once on table [{$this->table}]."
                );
            }

            $seen[$foreignKey->name()] = true;
        }

        return $this->foreignKeys;
    }

    /** @return array<int, string> */
    public function dropColumns(): array
    {
        return array_values(array_unique($this->dropColumns));
    }

    /** @return array<string, string> */
    public function renameColumns(): array
    {
        return $this->renameColumns;
    }

    /** @return array<int, string> */
    public function dropIndexes(): array
    {
        return array_values(array_unique($this->dropIndexes));
    }

    /** @return array<int, string> */
    public function dropForeignKeys(): array
    {
        return array_values(array_unique($this->dropForeignKeys));
    }

    public function indexName(string $type, array $columns): string
    {
        $base = strtolower($this->table . '_' . implode('_', $columns) . '_' . $type);
        $base = preg_replace('/[^a-z0-9_]+/', '_', $base) ?? $base;

        if (strlen($base) <= 60) {
            return $base;
        }

        return substr($base, 0, 51) . '_' . substr(hash('sha256', $base), 0, 8);
    }

    private function column(
        string $name,
        string $type,
        ?int $length = null,
        ?int $precision = null,
        ?int $scale = null
    ): ColumnDefinition {
        return $this->addColumn(
            new ColumnDefinition($this, $name, $type, $length, $precision, $scale)
        );
    }

    /** @template T of ColumnDefinition @param T $column @return T */
    private function addColumn(ColumnDefinition $column): ColumnDefinition
    {
        $this->assertIdentifier($column->name());

        foreach ($this->columns as $existing) {
            if ($existing->name() === $column->name()) {
                throw new InvalidArgumentException(
                    "Column [{$column->name()}] is already defined on table [{$this->table}]."
                );
            }
        }

        $this->columns[] = $column;

        return $column;
    }

    /** @return array<int, string> */
    private function normalizeColumns(string|array $columns): array
    {
        $columns = is_array($columns) ? array_values($columns) : [$columns];

        if ($columns === []) {
            throw new InvalidArgumentException('Columns cannot be empty.');
        }

        foreach ($columns as $column) {
            if (!is_string($column)) {
                throw new InvalidArgumentException('Column names must be strings.');
            }

            $this->assertIdentifier($column);
        }

        return $columns;
    }

    private function assertIdentifier(string $identifier): void
    {
        foreach (explode('.', $identifier) as $part) {
            if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $part)) {
                throw new InvalidArgumentException("Invalid SQL identifier: {$identifier}");
            }
        }
    }
}
