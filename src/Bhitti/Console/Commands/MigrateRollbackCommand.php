<?php

declare(strict_types=1);

namespace Bhitti\Console\Commands;

use Bhitti\Console\Input;
use Bhitti\Console\Output;
use InvalidArgumentException;

final class MigrateRollbackCommand extends MigrationCommand
{
    public function handle(Input $input, Output $output): int
    {
        $this->requireForce($input, 'rollback');

        $step = $this->step($input);

        $rolledBack = $this->migrator($input)->rollback(
            $step,
            $input->hasOption('allow-modified')
        );

        if ($rolledBack === []) {
            $output->line('Nothing to roll back.');

            return 0;
        }

        foreach ($rolledBack as $migration) {
            $output->line("[DOWN] {$migration}");
        }

        $output->success(
            'Rolled back '
            . count($rolledBack)
            . ' migration(s).'
        );

        return 0;
    }

    private function step(Input $input): ?int
    {
        $step = $input->option('step');

        if ($step === null) {
            return null;
        }

        if ($step === true) {
            throw new InvalidArgumentException(
                'The --step option requires a value.'
            );
        }

        $step = filter_var(
            $step,
            FILTER_VALIDATE_INT,
            [
                'options' => [
                    'min_range' => 1,
                ],
            ]
        );

        if ($step === false) {
            throw new InvalidArgumentException(
                'The --step option must be a positive integer.'
            );
        }

        return $step;
    }
}