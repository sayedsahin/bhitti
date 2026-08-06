<?php

declare(strict_types=1);

namespace Bhitti\Console;

final class Input
{
    private array $arguments = [];
    private array $options = [];

    public function __construct(array $tokens)
    {
        $count = count($tokens);

        for ($index = 0; $index < $count; $index++) {
            $token = $tokens[$index];

            if (!str_starts_with($token, '--')) {
                $this->arguments[] = $token;

                continue;
            }

            $option = substr($token, 2);

            if (str_contains($option, '=')) {
                [$name, $value] = explode('=', $option, 2);

                $this->options[$name] = $value;

                continue;
            }

            $next = $tokens[$index + 1] ?? null;

            if (is_string($next) && !str_starts_with($next, '-')) {
                $this->options[$option] = $next;
                $index++;

                continue;
            }

            $this->options[$option] = true;
        }
    }

    public function argument(int $index, mixed $default = null): mixed
    {
        return $this->arguments[$index] ?? $default;
    }

    public function arguments(): array
    {
        return $this->arguments;
    }

    public function option(string $name, mixed $default = null): mixed
    {
        return $this->options[$name] ?? $default;
    }

    public function hasOption(string $name): bool
    {
        return array_key_exists($name, $this->options);
    }
}