<?php

declare(strict_types=1);

namespace Bhitti\Database\Migration;

use Bhitti\Database\Migration\Grammar\Grammar;
use Bhitti\Database\Migration\Grammar\GrammarFactory;
use PDO;
use RuntimeException;

final class SchemaManager
{
    private readonly PDO $pdo;
    private readonly string $driver;
    private readonly Grammar $grammar;

    public function __construct(
        PDO $pdo,
        string $driver,
        private readonly ?string $connectionName = null
    ) {
        $this->pdo = $pdo;
        $this->driver = $driver;
        $this->grammar = GrammarFactory::make($driver);
    }

    public function create(string $table, callable $callback): void
    {
        $blueprint = new Blueprint($table, true);
        $callback($blueprint);
        $this->executeStatements($this->grammar->compileCreate($blueprint));
    }

    public function createIfNotExists(string $table, callable $callback): void
    {
        if ($this->hasTable($table)) {
            return;
        }

        $this->create($table, $callback);
    }

    public function table(string $table, callable $callback): void
    {
        if (!$this->hasTable($table)) {
            throw new RuntimeException("Table [{$table}] does not exist.");
        }

        $blueprint = new Blueprint($table, false);
        $callback($blueprint);
        $this->validateAlterBlueprint($blueprint);

        $statements = $this->grammar->compileAlter($blueprint);

        if ($statements === []) {
            throw new RuntimeException("No schema changes were defined for table [{$table}].");
        }

        $this->executeStatements($statements);
    }

    public function drop(string $table): void
    {
        $this->pdo->exec($this->grammar->compileDrop($table));
    }

    public function dropIfExists(string $table): void
    {
        $this->pdo->exec($this->grammar->compileDropIfExists($table));
    }

    public function rename(string $from, string $to): void
    {
        $this->pdo->exec($this->grammar->compileRename($from, $to));
    }

    public function hasTable(string $table): bool
    {
        return match ($this->driver) {
            'mysql' => $this->hasMySqlTable($table),
            'pgsql' => $this->hasPostgresTable($table),
            'sqlite' => $this->hasSqliteTable($table),
        };
    }

    public function hasColumn(string $table, string $column): bool
    {
        return match ($this->driver) {
            'mysql' => $this->hasMySqlColumn($table, $column),
            'pgsql' => $this->hasPostgresColumn($table, $column),
            'sqlite' => $this->hasSqliteColumn($table, $column),
        };
    }

    public function statement(string $sql, array $bindings = []): int
    {
        if ($bindings === []) {
            return (int) $this->pdo->exec($sql);
        }

        $statement = $this->pdo->prepare($sql);
        $statement->execute(array_values($bindings));

        return $statement->rowCount();
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }

    public function driver(): string
    {
        return $this->driver;
    }

    public function connectionName(): ?string
    {
        return $this->connectionName;
    }

    public function grammar(): Grammar
    {
        return $this->grammar;
    }

    private function validateAlterBlueprint(Blueprint $blueprint): void
    {
        $table = $blueprint->table();
        $newColumns = [];
        $droppedColumns = array_fill_keys($blueprint->dropColumns(), true);
        $renamedColumns = $blueprint->renameColumns();
        $renamedTargets = [];
        $existing = [];

        $hasExisting = function (string $column) use ($table, &$existing): bool {
            return $existing[$column] ??= $this->hasColumn($table, $column);
        };

        foreach ($blueprint->columns() as $column) {
            $name = $column->name();
            $newColumns[$name] = true;

            if ($hasExisting($name) && !isset($droppedColumns[$name])) {
                throw new RuntimeException(
                    "Cannot add column [{$name}] because it already exists on table [{$table}]."
                );
            }
        }

        foreach ($droppedColumns as $column => $_) {
            if (!$hasExisting($column)) {
                throw new RuntimeException(
                    "Cannot drop missing column [{$column}] from table [{$table}]."
                );
            }

            if (isset($renamedColumns[$column])) {
                throw new RuntimeException(
                    "Column [{$column}] cannot be dropped and renamed in the same schema operation."
                );
            }
        }

        foreach ($renamedColumns as $from => $to) {
            if (!$hasExisting($from)) {
                throw new RuntimeException(
                    "Cannot rename missing column [{$from}] on table [{$table}]."
                );
            }

            if (isset($newColumns[$from]) || isset($newColumns[$to])) {
                throw new RuntimeException(
                    "Renamed columns cannot also be added in the same schema operation: [{$from}] to [{$to}]."
                );
            }

            if (isset($renamedTargets[$to])) {
                throw new RuntimeException(
                    "Multiple columns cannot be renamed to [{$to}] on table [{$table}]."
                );
            }

            if ($hasExisting($to) && !isset($droppedColumns[$to])) {
                throw new RuntimeException(
                    "Cannot rename column [{$from}] to [{$to}] because [{$to}] already exists."
                );
            }

            $renamedTargets[$to] = true;
        }

        $isAvailable = function (string $column) use (
            $hasExisting,
            $newColumns,
            $droppedColumns,
            $renamedColumns,
            $renamedTargets
        ): bool {
            if (isset($newColumns[$column]) || isset($renamedTargets[$column])) {
                return true;
            }

            if (isset($droppedColumns[$column]) || isset($renamedColumns[$column])) {
                return false;
            }

            return $hasExisting($column);
        };

        $primaryIndexes = array_values(array_filter(
            $blueprint->indexes(),
            static fn (IndexDefinition $index): bool => $index->type === 'primary'
        ));

        if (count($primaryIndexes) > 1) {
            throw new RuntimeException(
                "Table [{$table}] defines more than one primary-key addition."
            );
        }

        foreach ($blueprint->indexes() as $index) {
            foreach ($index->columns as $column) {
                if (!$isAvailable($column)) {
                    throw new RuntimeException(
                        "Index [{$index->name}] references missing column [{$column}] on table [{$table}]."
                    );
                }
            }
        }

        foreach ($blueprint->foreignKeys() as $foreignKey) {
            foreach ($foreignKey->columns() as $column) {
                if (!$isAvailable($column)) {
                    throw new RuntimeException(
                        "Foreign key [{$foreignKey->name()}] references missing column [{$column}] on table [{$table}]."
                    );
                }
            }
        }
    }

    /** @param array<int, string> $statements */
    private function executeStatements(array $statements): void
    {
        foreach ($statements as $statement) {
            $this->pdo->exec($statement);
        }
    }

    private function hasMySqlTable(string $table): bool
    {
        $statement = $this->pdo->prepare(
            'SELECT 1 FROM information_schema.tables '
            . 'WHERE table_schema = DATABASE() AND table_name = ? LIMIT 1'
        );
        $statement->execute([$table]);

        return $statement->fetchColumn() !== false;
    }

    private function hasPostgresTable(string $table): bool
    {
        $statement = $this->pdo->prepare(
            'SELECT 1 FROM information_schema.tables '
            . 'WHERE table_schema = current_schema() AND table_name = ? LIMIT 1'
        );
        $statement->execute([$table]);

        return $statement->fetchColumn() !== false;
    }

    private function hasSqliteTable(string $table): bool
    {
        $statement = $this->pdo->prepare(
            "SELECT 1 FROM sqlite_master WHERE type = 'table' AND name = ? LIMIT 1"
        );
        $statement->execute([$table]);

        return $statement->fetchColumn() !== false;
    }

    private function hasMySqlColumn(string $table, string $column): bool
    {
        $statement = $this->pdo->prepare(
            'SELECT 1 FROM information_schema.columns '
            . 'WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ? LIMIT 1'
        );
        $statement->execute([$table, $column]);

        return $statement->fetchColumn() !== false;
    }

    private function hasPostgresColumn(string $table, string $column): bool
    {
        $statement = $this->pdo->prepare(
            'SELECT 1 FROM information_schema.columns '
            . 'WHERE table_schema = current_schema() AND table_name = ? AND column_name = ? LIMIT 1'
        );
        $statement->execute([$table, $column]);

        return $statement->fetchColumn() !== false;
    }

    private function hasSqliteColumn(string $table, string $column): bool
    {
        $statement = $this->pdo->query(
            'PRAGMA table_info(' . $this->grammar->wrap($table) . ')'
        );

        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $row) {
            if (($row['name'] ?? null) === $column) {
                return true;
            }
        }

        return false;
    }
}
