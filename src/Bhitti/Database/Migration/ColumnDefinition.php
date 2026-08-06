<?php

declare(strict_types=1);

namespace Bhitti\Database\Migration;

use InvalidArgumentException;

class ColumnDefinition
{
    private bool $nullable = false;
    private bool $unsigned = false;
    private bool $autoIncrement = false;
    private bool $primary = false;
    private bool $defaultDefined = false;
    private mixed $default = null;
    private ?string $uniqueName = null;
    private ?string $indexName = null;

    public function __construct(
        protected readonly Blueprint $blueprint,
        private readonly string $name,
        private readonly string $type,
        private readonly ?int $length = null,
        private readonly ?int $precision = null,
        private readonly ?int $scale = null
    ) {
    }

    public function nullable(bool $value = true): static
    {
        $this->nullable = $value;

        return $this;
    }

    public function unsigned(bool $value = true): static
    {
        $this->unsigned = $value;

        return $this;
    }

    public function autoIncrement(bool $value = true): static
    {
        $this->autoIncrement = $value;

        return $this;
    }

    public function primary(bool $value = true): static
    {
        $this->primary = $value;

        return $this;
    }

    public function default(mixed $value): static
    {
        if (is_array($value) || is_object($value) && !$value instanceof Expression) {
            throw new InvalidArgumentException('Column default must be scalar, null, or an SQL Expression.');
        }

        $this->defaultDefined = true;
        $this->default = $value;

        return $this;
    }

    public function useCurrent(): static
    {
        return $this->default(new Expression('CURRENT_TIMESTAMP'));
    }

    public function unique(?string $name = null): static
    {
        $this->uniqueName = $name ?? $this->blueprint->indexName('unique', [$this->name]);

        return $this;
    }

    public function index(?string $name = null): static
    {
        $this->indexName = $name ?? $this->blueprint->indexName('index', [$this->name]);

        return $this;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function type(): string
    {
        return $this->type;
    }

    public function length(): ?int
    {
        return $this->length;
    }

    public function precision(): ?int
    {
        return $this->precision;
    }

    public function scale(): ?int
    {
        return $this->scale;
    }

    public function isNullable(): bool
    {
        return $this->nullable;
    }

    public function isUnsigned(): bool
    {
        return $this->unsigned;
    }

    public function isAutoIncrement(): bool
    {
        return $this->autoIncrement;
    }

    public function isPrimary(): bool
    {
        return $this->primary;
    }

    public function hasDefault(): bool
    {
        return $this->defaultDefined;
    }

    public function defaultValue(): mixed
    {
        return $this->default;
    }

    public function uniqueName(): ?string
    {
        return $this->uniqueName;
    }

    public function indexName(): ?string
    {
        return $this->indexName;
    }
}
