<?php

declare(strict_types=1);

namespace Bhitti\Database\Migration;

use Bhitti\Database\Database;
use RuntimeException;
use Throwable;

final class Migrator
{
    private readonly string $connectionName;
    private readonly string $driver;
    private readonly SchemaManager $schema;
    private readonly MigrationLoader $loader;
    private readonly MigrationRepository $repository;
    private readonly MigrationLock $lock;

    public function __construct(
        private readonly Database $database,
        ?string $connection,
        string $migrationPath,
        string $repositoryTable = 'migrations',
        string $lockTable = 'migration_locks'
    ) {
        $this->connectionName = trim($connection ?? (string) config('database.default'));

        if ($this->connectionName === '') {
            throw new RuntimeException('Migration connection name cannot be empty.');
        }

        $pdo = $database->connection($this->connectionName);
        $this->driver = $database->driver($this->connectionName);
        $this->schema = new SchemaManager($pdo, $this->driver, $this->connectionName);
        $this->loader = new MigrationLoader($migrationPath);
        $this->repository = new MigrationRepository($pdo, $this->schema, $repositoryTable);
        $this->lock = new MigrationLock($pdo, $this->schema, $lockTable);
    }

    /** @return array<int, string> */
    public function migrate(): array
    {
        $this->repository->ensureTable();

        return $this->withLock(fn (): array => $this->withSchema(function (): array {
            $files = $this->loader->files();
            $ran = $this->repository->all();
            $this->assertHistoryFilesExist($files, $ran);
            $this->assertExecutedFilesUnchanged($files, $ran);

            $pending = array_diff_key($files, $ran);

            if ($pending === []) {
                return [];
            }

            $batch = $this->repository->nextBatchNumber();
            $executed = [];

            foreach ($pending as $name => $file) {
                $checksum = $this->loader->checksum($file);

                $this->runAtomically(function () use ($name, $file, $batch, $checksum): void {
                    $migration = $this->loader->load($file);
                    $migration['up']();
                    $this->repository->log($name, $batch, $checksum);
                });

                $executed[] = $name;
            }

            return $executed;
        }));
    }

    /**
     * Without $step, rolls back the complete latest batch.
     * With $step, rolls back the latest N migrations.
     *
     * @return array<int, string>
     */
    public function rollback(?int $step = null, bool $allowModified = false): array
    {
        if ($step !== null && $step < 1) {
            throw new RuntimeException('Rollback step must be greater than zero.');
        }

        $this->repository->ensureTable();

        return $this->withLock(fn (): array => $this->withSchema(function () use ($step, $allowModified): array {
            $files = $this->loader->files();
            $records = $step === null
                ? $this->repository->lastBatch()
                : $this->repository->lastMigrations($step);

            if ($records === []) {
                return [];
            }

            $rolledBack = [];

            foreach ($records as $record) {
                $name = $record['migration'];
                $file = $files[$name] ?? null;

                if ($file === null) {
                    throw new RuntimeException(
                        "Cannot roll back migration [{$name}] because its file is missing."
                    );
                }

                $checksum = $this->loader->checksum($file);

                if (!$allowModified && !hash_equals($record['checksum'], $checksum)) {
                    throw new RuntimeException(
                        "Cannot roll back modified migration [{$name}]. Restore the original file or use --allow-modified."
                    );
                }

                $this->runAtomically(function () use ($name, $file): void {
                    $migration = $this->loader->load($file);
                    $migration['down']();
                    $this->repository->delete($name);
                });

                $rolledBack[] = $name;
            }

            return $rolledBack;
        }));
    }

    /**
     * @return array<int, array{
     *     migration: string,
     *     status: string,
     *     batch: int|null,
     *     executed_at: string|null
     * }>
     */
    public function status(): array
    {
        $this->repository->ensureTable();
        $files = $this->loader->files();
        $ran = $this->repository->all();
        $names = array_values(array_unique(array_merge(array_keys($files), array_keys($ran))));
        sort($names, SORT_STRING);
        $status = [];

        foreach ($names as $name) {
            $record = $ran[$name] ?? null;
            $file = $files[$name] ?? null;
            $state = 'pending';

            if ($record !== null && $file === null) {
                $state = 'missing';
            } elseif ($record !== null && $file !== null) {
                $state = hash_equals($record['checksum'], $this->loader->checksum($file))
                    ? 'ran'
                    : 'modified';
            }

            $status[] = [
                'migration' => $name,
                'status' => $state,
                'batch' => $record['batch'] ?? null,
                'executed_at' => $record['executed_at'] ?? null,
            ];
        }

        return $status;
    }

    public function connectionName(): string
    {
        return $this->connectionName;
    }

    public function driver(): string
    {
        return $this->driver;
    }

    public function migrationPath(): string
    {
        return $this->loader->path();
    }

    private function withLock(callable $callback): mixed
    {
        $this->lock->acquire($this->lockName());
        $failure = null;

        try {
            return $callback();
        } catch (Throwable $exception) {
            $failure = $exception;
            throw $exception;
        } finally {
            try {
                $this->lock->release($this->lockName());
            } catch (Throwable $releaseException) {
                if ($failure === null) {
                    throw $releaseException;
                }
            }
        }
    }

    private function runAtomically(callable $callback): mixed
    {
        if ($this->driver === 'mysql' || $this->schema->pdo()->inTransaction()) {
            return $callback();
        }

        return $this->database->transaction($callback, $this->connectionName);
    }

    private function withSchema(callable $callback): mixed
    {
        Schema::setManager($this->schema);

        try {
            return $callback();
        } finally {
            Schema::clearManager();
        }
    }

    /**
     * @param array<string, string> $files
     * @param array<string, array{migration: string, batch: int, checksum: string, executed_at: string}> $ran
     */
    private function assertHistoryFilesExist(array $files, array $ran): void
    {
        $missing = array_diff_key($ran, $files);

        if ($missing !== []) {
            throw new RuntimeException(
                'Executed migration files are missing: ' . implode(', ', array_keys($missing))
            );
        }
    }

    /**
     * @param array<string, string> $files
     * @param array<string, array{migration: string, batch: int, checksum: string, executed_at: string}> $ran
     */
    private function assertExecutedFilesUnchanged(array $files, array $ran): void
    {
        foreach ($ran as $name => $record) {
            $checksum = $this->loader->checksum($files[$name]);

            if (!hash_equals($record['checksum'], $checksum)) {
                throw new RuntimeException(
                    "Executed migration [{$name}] has been modified. Create a new migration instead."
                );
            }
        }
    }

    private function lockName(): string
    {
        return 'migrations';
    }
}
