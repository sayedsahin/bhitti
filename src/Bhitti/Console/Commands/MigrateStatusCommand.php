<?php

declare(strict_types=1);

namespace Bhitti\Console\Commands;

use Bhitti\Console\Input;
use Bhitti\Console\Output;

final class MigrateStatusCommand extends MigrationCommand
{
    public function handle(Input $input, Output $output): int
    {
        $migrator = $this->migrator($input);
        $rows = $migrator->status();

        $output->line(
            "Connection: {$migrator->connectionName()} "
            . "({$migrator->driver()})"
        );

        $output->line();

        if ($rows === []) {
            $output->line('No migrations found.');

            return 0;
        }

        $width = max(
            9,
            ...array_map(
                static fn (array $row): int => strlen(
                    $row['migration']
                ),
                $rows
            )
        );

        $output->line(
            str_pad('Migration', $width)
            . '  Status    Batch  Executed at'
        );

        $output->line(
            str_repeat('-', $width)
            . '  --------  -----  -------------------'
        );

        foreach ($rows as $row) {
            $batch = $row['batch'] === null
                ? '-'
                : (string) $row['batch'];

            $executedAt = $row['executed_at'] ?? '-';

            $output->line(
                str_pad($row['migration'], $width)
                . '  '
                . str_pad($row['status'], 8)
                . '  '
                . str_pad($batch, 5)
                . "  {$executedAt}"
            );
        }

        return 0;
    }
}