<?php

declare(strict_types=1);

namespace Bhitti\Console\Commands;

use Bhitti\Console\CommandInterface;
use Bhitti\Console\Input;
use Bhitti\Console\Output;
use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use RuntimeException;

final class MakeMigrationCommand implements CommandInterface
{
    public function handle(Input $input, Output $output): int
    {
        $name = $this->migrationName($input);
        $createTable = $this->createTable($input, $name);
        $alterTable = $this->optionValue($input, 'table');

        if ($createTable !== null && $alterTable !== null) {
            throw new InvalidArgumentException('Use either --create or --table, not both.');
        }

        if ($createTable !== null) {
            $this->assertTableName($createTable);
        }

        if ($alterTable !== null) {
            $this->assertTableName($alterTable);
        }

        $directory = $this->migrationPath($input);
        $this->ensureDirectory($directory);

        $lock = fopen($directory . '/.migration-create.lock', 'c+');

        if ($lock === false) {
            throw new RuntimeException("Unable to open migration creation lock in [{$directory}].");
        }

        try {
            if (!flock($lock, LOCK_EX)) {
                throw new RuntimeException('Unable to acquire the migration creation lock.');
            }

            [$filename, $path] = $this->availablePath($directory, $name);
            $content = $this->migrationContent($createTable, $alterTable);

            $file = fopen($path, 'x');

            if ($file === false) {
                throw new RuntimeException("Unable to create migration file [{$path}].");
            }

            try {
                $this->write($file, $content . PHP_EOL, $path);
            } finally {
                fclose($file);
            }
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }

        $output->success("Created migration: {$filename}");
        $output->line("Path: {$path}");

        return 0;
    }

    private function migrationName(Input $input): string
    {
        $name = trim((string) $input->argument(0, ''));
        $name = preg_replace('/(?<!^)[A-Z]/', '_$0', $name) ?? $name;
        $name = strtolower($name);
        $name = preg_replace('/[^a-z0-9]+/', '_', $name) ?? $name;
        $name = trim($name, '_');

        if ($name === '' || preg_match('/^[a-z][a-z0-9_]*$/', $name) !== 1) {
            throw new InvalidArgumentException(
                'Usage: php run make:migration migration_name [--create=table] [--table=table] [--path=path]'
            );
        }

        return $name;
    }

    private function createTable(Input $input, string $name): ?string
    {
        $value = $input->option('create');

        if ($value === null) {
            return preg_match('/^create_(.+)_table$/', $name, $matches) === 1
                ? $matches[1]
                : null;
        }

        if ($value === true) {
            if (preg_match('/^create_(.+)_table$/', $name, $matches) !== 1) {
                throw new InvalidArgumentException(
                    'The --create option requires a table name unless the migration name uses create_*_table.'
                );
            }

            return $matches[1];
        }

        $value = trim((string) $value);

        if ($value === '') {
            throw new InvalidArgumentException('The --create option requires a table name.');
        }

        return $value;
    }

    private function optionValue(Input $input, string $name): ?string
    {
        $value = $input->option($name);

        if ($value === null) {
            return null;
        }

        if ($value === true) {
            throw new InvalidArgumentException("The --{$name} option requires a value.");
        }

        $value = trim((string) $value);

        if ($value === '') {
            throw new InvalidArgumentException("The --{$name} option cannot be empty.");
        }

        return $value;
    }

    private function assertTableName(string $table): void
    {
        if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $table) !== 1) {
            throw new InvalidArgumentException("Invalid table name [{$table}].");
        }
    }

    private function migrationPath(Input $input): string
    {
        $path = $this->optionValue($input, 'path') ?? ROOT_PATH . '/database/migrations';

        if (!$this->isAbsolutePath($path)) {
            $path = ROOT_PATH . DIRECTORY_SEPARATOR . ltrim($path, '/\\');
        }

        return rtrim($path, '/\\');
    }

    private function isAbsolutePath(string $path): bool
    {
        return str_starts_with($path, DIRECTORY_SEPARATOR)
            || preg_match('/^[A-Za-z]:[\\\\\/]/', $path) === 1;
    }

    private function ensureDirectory(string $directory): void
    {
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException("Unable to create migration directory [{$directory}].");
        }

        if (!is_writable($directory)) {
            throw new RuntimeException("Migration directory is not writable [{$directory}].");
        }
    }

    private function availablePath(string $directory, string $name): array
    {
        do {
            $timestamp = (new DateTimeImmutable('now', new DateTimeZone('UTC')))
                ->format('Ymd_His_u');

            $filename = "{$timestamp}_{$name}.php";
            $path = $directory . DIRECTORY_SEPARATOR . $filename;
        } while (is_file($path));

        return [$filename, $path];
    }

    private function migrationContent(?string $createTable, ?string $alterTable): string
    {
        if ($createTable !== null) {
            $table = var_export($createTable, true);

            return <<<PHP
<?php

declare(strict_types=1);

use Bhitti\Database\Migration\Blueprint;
use Bhitti\Database\Migration\Schema;

return [
    'up' => static function (): void {
        Schema::create({$table}, static function (Blueprint \$table): void {
            \$table->id();
            \$table->timestamps();
        });
    },

    'down' => static function (): void {
        Schema::dropIfExists({$table});
    },
];
PHP;
        }

        if ($alterTable !== null) {
            $table = var_export($alterTable, true);

            return <<<PHP
<?php

declare(strict_types=1);

use Bhitti\Database\Migration\Blueprint;
use Bhitti\Database\Migration\Schema;

return [
    'up' => static function (): void {
        Schema::table({$table}, static function (Blueprint \$table): void {
            // \$table->string('column_name')->nullable();
        });
    },

    'down' => static function (): void {
        Schema::table({$table}, static function (Blueprint \$table): void {
            // \$table->dropColumn('column_name');
        });
    },
];
PHP;
        }

        return <<<'PHP'
<?php

declare(strict_types=1);

use Bhitti\Database\Migration\Schema;

return [
    'up' => static function (): void {
        // Schema changes go here.
    },

    'down' => static function (): void {
        // Reverse the schema changes here.
    },
];
PHP;
    }

    /** @param resource $file */
    private function write($file, string $content, string $path): void
    {
        $length = strlen($content);
        $written = 0;

        while ($written < $length) {
            $bytes = fwrite($file, substr($content, $written));

            if ($bytes === false || $bytes === 0) {
                throw new RuntimeException("Unable to write migration file [{$path}].");
            }

            $written += $bytes;
        }
    }
}