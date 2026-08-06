<?php

declare(strict_types=1);

namespace Bhitti\Database\Migration;

use PDO;

final class MigrationRepository
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly SchemaManager $schema,
        private readonly string $table = 'migrations'
    ) {
    }

    public function ensureTable(): void
    {
        $this->schema->createIfNotExists($this->table, static function (Blueprint $table): void {
            $table->string('migration', 255)->primary();
            $table->integer('batch');
            $table->string('checksum', 64);
            $table->timestamp('executed_at');
        });

        foreach (['migration', 'batch', 'checksum', 'executed_at'] as $column) {
            if (!$this->schema->hasColumn($this->table, $column)) {
                throw new \RuntimeException(
                    "Migration repository [{$this->table}] is missing required column [{$column}]."
                );
            }
        }
    }

    /** @return array<string, array{migration: string, batch: int, checksum: string, executed_at: string}> */
    public function all(): array
    {
        $table = $this->schema->grammar()->wrap($this->table);
        $statement = $this->pdo->query(
            "SELECT migration, batch, checksum, executed_at FROM {$table} ORDER BY migration ASC"
        );

        $rows = [];

        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $migration = (string) $row['migration'];
            $rows[$migration] = [
                'migration' => $migration,
                'batch' => (int) $row['batch'],
                'checksum' => (string) $row['checksum'],
                'executed_at' => (string) $row['executed_at'],
            ];
        }

        return $rows;
    }

    /** @return array<int, array{migration: string, batch: int, checksum: string, executed_at: string}> */
    public function lastBatch(): array
    {
        $batch = $this->lastBatchNumber();

        if ($batch === 0) {
            return [];
        }

        $table = $this->schema->grammar()->wrap($this->table);
        $statement = $this->pdo->prepare(
            "SELECT migration, batch, checksum, executed_at FROM {$table} WHERE batch = ? ORDER BY migration DESC"
        );
        $statement->execute([$batch]);

        return $this->normalizeRows($statement->fetchAll(PDO::FETCH_ASSOC));
    }

    /** @return array<int, array{migration: string, batch: int, checksum: string, executed_at: string}> */
    public function lastMigrations(int $limit): array
    {
        $limit = max(1, $limit);
        $table = $this->schema->grammar()->wrap($this->table);
        $statement = $this->pdo->query(
            "SELECT migration, batch, checksum, executed_at FROM {$table} "
            . 'ORDER BY batch DESC, migration DESC LIMIT ' . $limit
        );

        return $this->normalizeRows($statement->fetchAll(PDO::FETCH_ASSOC));
    }

    public function nextBatchNumber(): int
    {
        return $this->lastBatchNumber() + 1;
    }

    public function log(string $migration, int $batch, string $checksum): void
    {
        $table = $this->schema->grammar()->wrap($this->table);
        $statement = $this->pdo->prepare(
            "INSERT INTO {$table} (migration, batch, checksum, executed_at) VALUES (?, ?, ?, ?)"
        );
        $statement->execute([
            $migration,
            $batch,
            $checksum,
            gmdate('Y-m-d H:i:s'),
        ]);
    }

    public function delete(string $migration): void
    {
        $table = $this->schema->grammar()->wrap($this->table);
        $statement = $this->pdo->prepare("DELETE FROM {$table} WHERE migration = ?");
        $statement->execute([$migration]);
    }

    private function lastBatchNumber(): int
    {
        $table = $this->schema->grammar()->wrap($this->table);
        $value = $this->pdo->query("SELECT MAX(batch) FROM {$table}")->fetchColumn();

        return $value === false || $value === null ? 0 : (int) $value;
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array{migration: string, batch: int, checksum: string, executed_at: string}>
     */
    private function normalizeRows(array $rows): array
    {
        return array_map(
            static fn (array $row): array => [
                'migration' => (string) $row['migration'],
                'batch' => (int) $row['batch'],
                'checksum' => (string) $row['checksum'],
                'executed_at' => (string) $row['executed_at'],
            ],
            $rows
        );
    }
}
