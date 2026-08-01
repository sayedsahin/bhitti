<?php

declare(strict_types=1);

namespace Bhitti\Http\Middleware\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD| Attribute::IS_REPEATABLE)]
final class Middleware
{
    public function __construct(
        public readonly string $class,
        public readonly array $arguments = []
    ) {
    }
}