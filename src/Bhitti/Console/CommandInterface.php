<?php

declare(strict_types=1);

namespace Bhitti\Console;

interface CommandInterface
{
    public function handle(Input $input, Output $output): int;
}