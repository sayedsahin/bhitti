<?php

declare(strict_types=1);

namespace Bhitti\Console;

use Bhitti\Core\Container;
use RuntimeException;
use Throwable;

final class Application
{
    public function __construct(
        private readonly Container $container,
        private readonly CommandRegistry $commands,
        private readonly bool $debug = false
    ) {
    }

    public function run(array $argv): int
    {
        $commandName = $argv[1] ?? 'list';

        if (in_array($commandName, ['list', '--help', '-h'], true)) {
            $this->showCommands();

            return 0;
        }

        if (!$this->commands->has($commandName)) {
            fwrite(
                STDERR,
                "Unknown command: {$commandName}" . PHP_EOL
                . "Run `php run list` to see available commands."
                . PHP_EOL
            );

            return 1;
        }

        try {
            $definition = $this->commands->get($commandName);

            $command = $this->container->make(
                $definition['class']
            );

            if (!$command instanceof CommandInterface) {
                throw new RuntimeException(
                    "Command [{$definition['class']}] must implement "
                    . CommandInterface::class . '.'
                );
            }

            return $command->handle(
                new Input(array_slice($argv, 2)),
                new Output()
            );
        } catch (Throwable $exception) {
            fwrite(
                STDERR,
                "Command [{$commandName}] failed: "
                . $exception->getMessage()
                . PHP_EOL
            );

            if ($this->debug) {
                fwrite(
                    STDERR,
                    $exception->getTraceAsString()
                    . PHP_EOL
                );
            }

            return 1;
        }
    }

    private function showCommands(): void
    {
        $commands = $this->commands->all();

        fwrite(STDOUT, "Bhitti Command Line\n\n");
        fwrite(
            STDOUT,
            "Usage:\n  php run <command> [options]\n\n"
        );
        fwrite(STDOUT, "Available commands:\n");

        if ($commands === []) {
            fwrite(STDOUT, "  No commands registered.\n");

            return;
        }

        $width = max(
            array_map('strlen', array_keys($commands))
        );

        foreach ($commands as $name => $definition) {
            fwrite(
                STDOUT,
                sprintf(
                    "  %-{$width}s  %s\n",
                    $name,
                    $definition['description']
                )
            );
        }
    }
}