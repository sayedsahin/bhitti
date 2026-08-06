<?php

declare(strict_types=1);

namespace Bhitti\Database\Migration;

use PDO;
use PDOException;
use RuntimeException;

final class MigrationLock
{
    private ?string $token = null;

    public function __construct(
        private readonly PDO $pdo,
        private readonly SchemaManager $schema,
        private readonly string $table = 'migration_locks',
        private readonly int $ttl = 3600
    ) {
    }

    public function ensureTable(): void
    {
        $this->schema->createIfNotExists($this->table, static function (Blueprint $table): void {
            $table->string('name', 100)->primary();
            $table->string('token', 64);
            $table->bigInteger('acquired_at');
        });

        foreach (['name', 'token', 'acquired_at'] as $column) {
            if (!$this->schema->hasColumn($this->table, $column)) {
                throw new RuntimeException(
                    "Migration lock table [{$this->table}] is missing required column [{$column}]."
                );
            }
        }
    }

    public function acquire(string $name = 'migrations'): void
    {
        $this->ensureTable();
        $this->removeExpired($name);

        $token = bin2hex(random_bytes(32));
        $table = $this->schema->grammar()->wrap($this->table);
        $statement = $this->pdo->prepare(
            "INSERT INTO {$table} (name, token, acquired_at) VALUES (?, ?, ?)"
        );

        try {
            $statement->execute([$name, $token, time()]);
        } catch (PDOException $exception) {
            if (in_array((string) $exception->getCode(), ['23000', '23505'], true)) {
                throw new RuntimeException(
                    'Another migration process is already running.',
                    0,
                    $exception
                );
            }

            throw new RuntimeException(
                'Unable to acquire the migration lock: ' . $exception->getMessage(),
                0,
                $exception
            );
        }

        $this->token = $token;
    }

    public function release(string $name = 'migrations'): void
    {
        if ($this->token === null) {
            return;
        }

        $table = $this->schema->grammar()->wrap($this->table);
        $statement = $this->pdo->prepare(
            "DELETE FROM {$table} WHERE name = ? AND token = ?"
        );
        $statement->execute([$name, $this->token]);
        $this->token = null;
    }

    private function removeExpired(string $name): void
    {
        $table = $this->schema->grammar()->wrap($this->table);
        $statement = $this->pdo->prepare(
            "DELETE FROM {$table} WHERE name = ? AND acquired_at < ?"
        );
        $statement->execute([$name, time() - max(60, $this->ttl)]);
    }
}
