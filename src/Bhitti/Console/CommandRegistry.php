<?php

declare(strict_types=1);

namespace Bhitti\Console;

use InvalidArgumentException;

final class CommandRegistry
{
    private array $commands = [];

    public function addMany(array $commands): void
    {
        foreach ($commands as $name => $definition) {
            if (!is_string($name) || $name === '') {
                throw new InvalidArgumentException(
                    'Command name must be a non-empty string.'
                );
            }

            if (!is_array($definition)) {
                throw new InvalidArgumentException(
                    "Command [{$name}] definition must be an array."
                );
            }

            $this->add(
                $name,
                $definition['class'] ?? null,
                $definition['description'] ?? ''
            );
        }
    }

    public function add(
        string $name,
        mixed $commandClass,
        string $description = ''
    ): void {
        if (!is_string($commandClass) || $commandClass === '') {
            throw new InvalidArgumentException(
                "Command [{$name}] must define a valid class."
            );
        }

        if (isset($this->commands[$name])) {
            throw new InvalidArgumentException(
                "Command [{$name}] is already registered."
            );
        }

        $this->commands[$name] = [
            'class' => $commandClass,
            'description' => $description,
        ];
    }

    public function has(string $name): bool
    {
        return isset($this->commands[$name]);
    }

    public function get(string $name): array
    {
        if (!$this->has($name)) {
            throw new InvalidArgumentException(
                "Command [{$name}] is not registered."
            );
        }

        return $this->commands[$name];
    }

    public function all(): array
    {
        $commands = $this->commands;

        ksort($commands);

        return $commands;
    }
}