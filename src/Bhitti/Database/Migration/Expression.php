<?php

declare(strict_types=1);

namespace Bhitti\Database\Migration;

final readonly class Expression
{
    public function __construct(public string $value)
    {
    }
}
