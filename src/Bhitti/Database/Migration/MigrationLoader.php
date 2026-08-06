<?php

declare(strict_types=1);

namespace Bhitti\Database\Migration;

use Closure;
use RuntimeException;

final class MigrationLoader
{
    private const FILENAME_PATTERN = '/^(?<date>[0-9]{8})_(?<time>[0-9]{6})(?:_(?<micro>[0-9]{6}))?_(?<name>[A-Za-z0-9_]+)$/';

    public function __construct(private readonly string $path)
    {
    }

    /**
     * @return array<string, string> migration name => absolute file path
     */
    public function files(): array
    {
        if (!is_dir($this->path)) {
            throw new RuntimeException("Migration directory does not exist: {$this->path}");
        }

        $files = glob(rtrim($this->path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '*.php');

        if ($files === false) {
            throw new RuntimeException("Unable to read migration directory: {$this->path}");
        }

        $entries = [];

        foreach ($files as $file) {
            $migration = pathinfo($file, PATHINFO_FILENAME);

            if (!preg_match(self::FILENAME_PATTERN, $migration, $matches)) {
                throw new RuntimeException(
                    "Invalid migration filename [{$migration}.php]. Expected "
                    . 'YYYYMMDD_HHMMSS_UUUUUU_name.php.'
                );
            }

            $microseconds = $matches['micro'] !== '' ? $matches['micro'] : '000000';
            $entries[] = [
                'migration' => $migration,
                'file' => $file,
                'order' => $matches['date'] . $matches['time'] . $microseconds . $matches['name'],
            ];
        }

        usort(
            $entries,
            static fn (array $left, array $right): int => $left['order'] <=> $right['order']
        );

        $migrations = [];

        foreach ($entries as $entry) {
            $migration = $entry['migration'];

            if (isset($migrations[$migration])) {
                throw new RuntimeException("Duplicate migration name: {$migration}");
            }

            $migrations[$migration] = $entry['file'];
        }

        return $migrations;
    }

    /**
     * @return array{up: Closure, down: Closure}
     */
    public function load(string $file): array
    {
        $realPath = realpath($file);
        $basePath = realpath($this->path);

        if ($realPath === false || $basePath === false || !str_starts_with($realPath, $basePath . DIRECTORY_SEPARATOR)) {
            throw new RuntimeException("Migration file is outside the migration directory: {$file}");
        }

        $migration = require $realPath;

        if (!is_array($migration)) {
            throw new RuntimeException("Migration [{$file}] must return an array.");
        }

        $up = $migration['up'] ?? null;
        $down = $migration['down'] ?? null;

        if (!$up instanceof Closure) {
            throw new RuntimeException("Migration [{$file}] must define an [up] Closure.");
        }

        if (!$down instanceof Closure) {
            throw new RuntimeException("Migration [{$file}] must define a [down] Closure.");
        }

        return [
            'up' => $up,
            'down' => $down,
        ];
    }

    public function checksum(string $file): string
    {
        $checksum = hash_file('sha256', $file);

        if ($checksum === false) {
            throw new RuntimeException("Unable to calculate migration checksum: {$file}");
        }

        return $checksum;
    }

    public function path(): string
    {
        return $this->path;
    }
}
