<?php

declare(strict_types=1);

namespace Bhitti\Console;

final class Output
{
    public function line(string $message = ''): void
    {
        fwrite(STDOUT, $message . PHP_EOL);
    }

    public function error(string $message): void
    {
        fwrite(STDERR, $message . PHP_EOL);
    }

    public function success(string $message): void
    {
        $this->line("\033[0;32m✓ {$message}\033[0m");
    }

    public function warning(string $message): void
    {
        $this->line("\033[0;31m✕ {$message}\033[0m");
    }
}