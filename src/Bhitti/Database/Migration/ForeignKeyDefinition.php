<?php

declare(strict_types=1);

namespace Bhitti\Database\Migration;

use InvalidArgumentException;

class ForeignKeyDefinition
{
    /** @var array<int, string> */
    private array $referencedColumns = ['id'];
    private ?string $referencedTable = null;
    private ?string $deleteAction = null;
    private ?string $updateAction = null;

    /**
     * @param array<int, string> $columns
     */
    public function __construct(
        private readonly Blueprint $blueprint,
        private readonly array $columns,
        private readonly string $name
    ) {
    }

    public function references(string|array $columns): static
    {
        $columns = is_array($columns) ? array_values($columns) : [$columns];

        if ($columns === []) {
            throw new InvalidArgumentException('Referenced columns cannot be empty.');
        }

        $this->referencedColumns = $columns;

        return $this;
    }

    public function on(string $table): static
    {
        if (trim($table) === '') {
            throw new InvalidArgumentException('Referenced table cannot be empty.');
        }

        $this->referencedTable = $table;

        return $this;
    }

    public function onDelete(string $action): static
    {
        $this->deleteAction = $this->normalizeAction($action);

        return $this;
    }

    public function onUpdate(string $action): static
    {
        $this->updateAction = $this->normalizeAction($action);

        return $this;
    }

    public function cascadeOnDelete(): static
    {
        return $this->onDelete('cascade');
    }

    public function cascadeOnUpdate(): static
    {
        return $this->onUpdate('cascade');
    }

    public function restrictOnDelete(): static
    {
        return $this->onDelete('restrict');
    }

    public function restrictOnUpdate(): static
    {
        return $this->onUpdate('restrict');
    }

    public function nullOnDelete(): static
    {
        return $this->onDelete('set null');
    }

    public function nullOnUpdate(): static
    {
        return $this->onUpdate('set null');
    }

    public function noActionOnDelete(): static
    {
        return $this->onDelete('no action');
    }

    public function noActionOnUpdate(): static
    {
        return $this->onUpdate('no action');
    }

    /** @return array<int, string> */
    public function columns(): array
    {
        return $this->columns;
    }

    /** @return array<int, string> */
    public function referencedColumns(): array
    {
        return $this->referencedColumns;
    }

    public function referencedTable(): string
    {
        return $this->referencedTable
            ?? throw new InvalidArgumentException(
                "Foreign key [{$this->name}] must define a referenced table."
            );
    }

    public function deleteAction(): ?string
    {
        return $this->deleteAction;
    }

    public function updateAction(): ?string
    {
        return $this->updateAction;
    }

    public function name(): string
    {
        return $this->name;
    }

    private function normalizeAction(string $action): string
    {
        $action = strtoupper(trim(preg_replace('/\s+/', ' ', $action) ?? $action));

        if (!in_array($action, ['CASCADE', 'RESTRICT', 'SET NULL', 'NO ACTION', 'SET DEFAULT'], true)) {
            throw new InvalidArgumentException("Unsupported foreign key action: {$action}");
        }

        return $action;
    }
}
