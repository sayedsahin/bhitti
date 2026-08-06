<?php

declare(strict_types=1);

namespace Bhitti\Database\Migration;

final readonly class IndexDefinition
{
    /**
     * @param array<int, string> $columns
     */
    public function __construct(
        public string $type,
        public array $columns,
        public string $name
    ) {
    }
}
