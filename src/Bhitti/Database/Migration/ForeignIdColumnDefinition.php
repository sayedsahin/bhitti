<?php

declare(strict_types=1);

namespace Bhitti\Database\Migration;

final class ForeignIdColumnDefinition extends ColumnDefinition
{
    private ?ForeignKeyDefinition $foreignKey = null;

    public function constrained(?string $table = null, string $column = 'id', ?string $name = null): static
    {
        $table ??= $this->guessTable();

        $this->foreignKey = $this->blueprint
            ->foreign($this->name(), $name)
            ->references($column)
            ->on($table);

        return $this;
    }

    public function references(string|array $columns): static
    {
        $this->foreignKey()->references($columns);

        return $this;
    }

    public function on(string $table): static
    {
        $this->foreignKey()->on($table);

        return $this;
    }

    public function onDelete(string $action): static
    {
        $this->foreignKey()->onDelete($action);

        return $this;
    }

    public function onUpdate(string $action): static
    {
        $this->foreignKey()->onUpdate($action);

        return $this;
    }

    public function cascadeOnDelete(): static
    {
        $this->foreignKey()->cascadeOnDelete();

        return $this;
    }

    public function cascadeOnUpdate(): static
    {
        $this->foreignKey()->cascadeOnUpdate();

        return $this;
    }

    public function restrictOnDelete(): static
    {
        $this->foreignKey()->restrictOnDelete();

        return $this;
    }

    public function restrictOnUpdate(): static
    {
        $this->foreignKey()->restrictOnUpdate();

        return $this;
    }

    public function nullOnDelete(): static
    {
        $this->nullable();
        $this->foreignKey()->nullOnDelete();

        return $this;
    }

    public function nullOnUpdate(): static
    {
        $this->nullable();
        $this->foreignKey()->nullOnUpdate();

        return $this;
    }

    private function foreignKey(): ForeignKeyDefinition
    {
        return $this->foreignKey ??= $this->blueprint
            ->foreign($this->name())
            ->references('id')
            ->on($this->guessTable());
    }

    private function guessTable(): string
    {
        $name = preg_replace('/_id$/', '', $this->name()) ?? $this->name();

        return str_ends_with($name, 's') ? $name : $name . 's';
    }
}
