<?php

declare(strict_types=1);

namespace Bhitti\Console\Commands;

use Bhitti\Console\Input;
use Bhitti\Console\Output;

final class MigrateCommand extends MigrationCommand
{
    public function handle(Input $input, Output $output): int
    {
        $this->requireForce($input, 'migration');

        $migrator = $this->migrator($input);

        $output->line(
            "Connection: {$migrator->connectionName()} "
            . "({$migrator->driver()})"
        );

        $output->line(
            "Path: {$migrator->migrationPath()}"
        );

        $executed = $migrator->migrate();

        if ($executed === []) {
            $output->line('Nothing to migrate.');

            return 0;
        }

        foreach ($executed as $migration) {
            $output->line("[UP] {$migration}");
        }

        $output->success(
            'Migrated '
            . count($executed)
            . ' migration(s).'
        );

        return 0;
    }
}