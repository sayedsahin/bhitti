<?php

declare(strict_types=1);

namespace Bhitti\Console\Commands;

use Bhitti\Console\CommandInterface;
use Bhitti\Console\Input;
use Bhitti\Database\Database;
use Bhitti\Database\Migration\Migrator;
use InvalidArgumentException;
use RuntimeException;

abstract class MigrationCommand implements CommandInterface
{
    public function __construct(
        private readonly Database $database
    ) {
    }

    final protected function migrator(Input $input): Migrator
    {
        $connection = $this->connection($input);
        $path = $this->migrationPath($input);

        return new Migrator(
            $this->database,
            $connection,
            $path
        );
    }

    final protected function requireForce(
        Input $input,
        string $operation
    ): void {
        if (
            config('app.env') === 'production'
            && !$input->hasOption('force')
        ) {
            throw new RuntimeException(
                "Production {$operation} requires the --force option."
            );
        }
    }

    private function connection(Input $input): ?string
    {
        $connection = $input->option('connection');

        if ($connection === null) {
            return null;
        }

        if ($connection === true) {
            throw new InvalidArgumentException(
                'The --connection option requires a value.'
            );
        }

        $connection = trim((string) $connection);

        if ($connection === '') {
            throw new InvalidArgumentException(
                'Migration connection name cannot be empty.'
            );
        }

        return $connection;
    }

    private function migrationPath(Input $input): string
    {
        $path = $input->option(
            'path',
            ROOT_PATH . '/database/migrations'
        );

        if (!is_string($path) || trim($path) === '') {
            throw new InvalidArgumentException(
                'The migration path must be a non-empty string.'
            );
        }

        $path = trim($path);

        if (!$this->isAbsolutePath($path)) {
            $path = ROOT_PATH
                . DIRECTORY_SEPARATOR
                . ltrim($path, '/\\');
        }

        return $path;
    }

    private function isAbsolutePath(string $path): bool
    {
        return str_starts_with($path, DIRECTORY_SEPARATOR)
            || preg_match(
                '/^[A-Za-z]:[\\\\\/]/',
                $path
            ) === 1;
    }
}