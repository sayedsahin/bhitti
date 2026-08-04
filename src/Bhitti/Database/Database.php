<?php

declare(strict_types=1);

namespace Bhitti\Database;

use InvalidArgumentException;
use PDO;

final class Database
{
    private array $connections = [];
    private array $drivers = [];
    private array $config;
    private string $defaultConnection;

    public function __construct()
    {
        $this->config = config('database.connections');
        $this->defaultConnection = config('database.default');
    }

    public function connection(?string $name = null): PDO
    {
        $name = $this->connectionName($name);

        if (isset($this->connections[$name])) {
            return $this->connections[$name];
        }

        $config = $this->connectionConfig($name);
        $driver = $this->driverName($config);

        $this->drivers[$name] = $driver;

        return $this->connections[$name] = $this->connect($config, $driver);
    }

    public function driver(?string $name = null): string
    {
        $name = $this->connectionName($name);

        return $this->drivers[$name] ??= $this->driverName($this->connectionConfig($name));
    }

    private function connectionName(?string $name): string
    {
        $name = trim($name ?? $this->defaultConnection);

        if ($name === '') {
            throw new InvalidArgumentException('Database connection name cannot be empty.');
        }

        return $name;
    }

    private function connectionConfig(string $name): array
    {
        $config = $this->config[$name] ?? null;

        if (!is_array($config)) {
            throw new InvalidArgumentException("Database connection [{$name}] is not configured.");
        }

        return $config;
    }

    private function driverName(array $config): string
    {
        $driver = strtolower(trim(($config['driver'])));

        if (!in_array($driver, ['mysql', 'pgsql', 'sqlite'], true)) {
            throw new InvalidArgumentException("Unsupported database driver: {$driver}");
        }

        return $driver;
    }

    private function connect(array $config, string $driver): PDO
    {
        $dsn = $this->dsn($driver, $config);
        $username = $driver === 'sqlite' ? null : $config['username'];
        $password = $driver === 'sqlite' ? null : $config['password'];

        $pdo = new PDO($dsn, $username, $password, $this->options($driver, $config));

        if ($driver === 'sqlite') {
            $this->configureSqlite($pdo, $config);
        }

        return $pdo;
    }

    private function dsn(string $driver, array $config): string
    {
        return match ($driver) {
            'mysql' => $this->mysqlDsn($config),
            'pgsql' => $this->pgsqlDsn($config),
            'sqlite' => $this->sqliteDsn($config),
        };
    }

    private function mysqlDsn(array $config): string
    {
        $host = $config['host'];
        $port = $config['port'];
        $database = $config['database'];
        $charset = $config['charset'];

        return "mysql:host={$host};port={$port};dbname={$database};charset={$charset}";
    }

    private function pgsqlDsn(array $config): string
    {
        return "pgsql:host={$config['host']};port={$config['port']};dbname={$config['database']}";
    }

    private function sqliteDsn(array $config): string
    {
        $database = trim($config['database']);

        if ($database === '') {
            throw new InvalidArgumentException('SQLite database path cannot be empty.');
        }

        return $database === ':memory:' ? 'sqlite::memory:' : "sqlite:{$database}";
    }

    private function options(string $driver, array $config): array
    {
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];

        if ($driver !== 'sqlite') {
            $options[PDO::ATTR_EMULATE_PREPARES] = false;
            $options[PDO::ATTR_PERSISTENT] = $config['options']['persistent'];
        }

        return $options;
    }

    private function configureSqlite(PDO $pdo, array $config): void
    {
        $pdo->exec('PRAGMA foreign_keys = ' . ($config['foreign_keys'] ? 'ON' : 'OFF'));

        $busyTimeout = max(0, $config['busy_timeout']);
        $pdo->exec("PRAGMA busy_timeout = {$busyTimeout}");

        $journalMode = strtoupper(trim($config['journal_mode']));

        if ($journalMode !== '') {
            if (!in_array($journalMode, ['DELETE', 'TRUNCATE', 'PERSIST', 'MEMORY', 'WAL', 'OFF'], true)) {
                throw new InvalidArgumentException("Invalid SQLite journal mode: {$journalMode}");
            }

            $pdo->exec("PRAGMA journal_mode = {$journalMode}");
        }

        $synchronous = strtoupper(trim($config['synchronous']));

        if ($synchronous !== '') {
            if (!in_array($synchronous, ['OFF', 'NORMAL', 'FULL', 'EXTRA'], true)) {
                throw new InvalidArgumentException("Invalid SQLite synchronous mode: {$synchronous}");
            }

            $pdo->exec("PRAGMA synchronous = {$synchronous}");
        }
    }

    public function beginTransaction(?string $connection = null): bool
    {
        return $this->connection($connection)->beginTransaction();
    }

    public function commit(?string $connection = null): bool
    {
        return $this->connection($connection)->commit();
    }

    public function rollBack(?string $connection = null): bool
    {
        $pdo = $this->connection($connection);

        return $pdo->inTransaction() && $pdo->rollBack();
    }

    public function inTransaction(?string $connection = null): bool
    {
        return $this->connection($connection)->inTransaction();
    }

    public function transaction(callable $callback, ?string $connection = null): mixed
    {
        $pdo = $this->connection($connection);
        $pdo->beginTransaction();

        try {
            $result = $callback();

            $pdo->commit();

            return $result;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $e;
        }
    }
}
