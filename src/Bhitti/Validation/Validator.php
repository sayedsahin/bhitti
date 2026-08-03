<?php

declare(strict_types=1);

namespace Bhitti\Validation;

final class Validator
{
    private array $data;
    private array $errors = [];
    private array $nullable = [];
    private array $validatedFields = [];
    private bool $stopOnFirstFailure = false;
    private const BOOLEAN_VALUES = [true, false, 0, 1, '0', '1', 'true', 'false'];

    public static function make(array $data): self
    {
        return new self($data);
    }

    private function __construct(array $data)
    {
        $this->data = $data;
    }

    /* ===============================
       CONFIG
    =============================== */

    public function bail(): self
    {
        $this->stopOnFirstFailure = true;
        return $this;
    }

    /* ===============================
       INTERNAL HELPERS
    =============================== */

    private function fields(string|array $fields): array
    {
        $fields = is_array($fields)? $fields : [$fields];

        foreach ($fields as $field) {
            $this->validatedFields[$field] = true;
        }

        return $fields;
    }

    private function has(string $field): bool
    {
        return array_key_exists($field, $this->data);
    }

    private function value(string $field): mixed
    {
        return $this->data[$field] ?? null;
    }

    private function isNullable(string $field): bool
    {
        return isset($this->nullable[$field]);
    }

    private function error(string $field, string $message): void
    {
        $this->errors[$field][] = $message;

        if ($this->stopOnFirstFailure) {
            throw new ValidationException($this->errors);
        }
    }

    /* ===============================
       STATUS
    =============================== */

    public function fails(): bool
    {
        return ! empty($this->errors);
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function validated(): array
    {
        if ($this->fails()) {
            throw new ValidationException($this->errors);
        }

        $validated = [];

        foreach (array_keys($this->validatedFields) as $field) {
            if ($this->has($field)) {
                $validated[$field] = $this->data[$field];
            }
        }

        return $validated;
    }

    /* ===============================
       RULES
    =============================== */

    public function nullable(string|array $fields): self
    {
        foreach ($this->fields($fields) as $field) {
            $this->nullable[$field] = true;
        }
        return $this;
    }

    public function required(string|array $fields): self
    {
        foreach ($this->fields($fields) as $field) {
            $value = $this->value($field);
            if (
                !$this->has($field)
                || $value === null
                || $value === ''
                || (is_string($value) && trim($value) === '')
                || (is_array($value) && $value === [])
            ) {
                $this->error($field, 'This field is required.');
            }
        }
        return $this;
    }

    public function string(string|array $fields): self
    {
        foreach ($this->fields($fields) as $field) {
            $v = $this->value($field);

            if ($v === null && $this->isNullable($field)) continue;

            if (! is_string($v)) {
                $this->error($field, 'Must be a string.');
            }
        }
        return $this;
    }

    public function int(string|array $fields): self
    {
        foreach ($this->fields($fields) as $field) {
            $v = $this->value($field);

            if ($v === null && $this->isNullable($field)) continue;

            if (filter_var($v, FILTER_VALIDATE_INT) === false) {
                $this->error($field, 'Must be a numeric.');
            }
        }
        return $this;
    }

    public function bool(string|array $fields): self
    {
        foreach ($this->fields($fields) as $field) {
            $v = $this->value($field);

            if ($v === null && $this->isNullable($field)) continue;

            if (!in_array($v, self::BOOLEAN_VALUES, true)) {
                $this->error($field, 'Must be boolean.');
            }
        }
        return $this;
    }

    public function email(string|array $fields): self
    {
        foreach ($this->fields($fields) as $field) {
            $v = $this->value($field);

            if ($v === null && $this->isNullable($field)) continue;

            if (! is_string($v) || ! filter_var($v, FILTER_VALIDATE_EMAIL)) {
                $this->error($field, 'Invalid email.');
            }
        }
        return $this;
    }

    public function min(string|array $fields, int $min): self
    {
        foreach ($this->fields($fields) as $field) {
            $v = $this->value($field);

            if ($v === null && $this->isNullable($field)) continue;

            if (mb_strlen((string)$v) < $min) {
                $this->error($field, "Minimum {$min} characters.");
            }
        }
        return $this;
    }

    public function max(string|array $fields, int $max): self
    {
        foreach ($this->fields($fields) as $field) {
            $v = $this->value($field);

            if ($v === null && $this->isNullable($field)) continue;

            if (mb_strlen((string)$v) > $max) {
                $this->error($field, "Maximum {$max} characters.");
            }
        }
        return $this;
    }

    public function in(string|array $fields, array $allowed): self
    {
        foreach ($this->fields($fields) as $field) {
            $v = $this->value($field);

            if ($v === null && $this->isNullable($field)) continue;

            if (! in_array($v, $allowed, true)) {
                $this->error($field, 'Invalid value.');
            }
        }
        return $this;
    }

    public function confirmed(string $field): self
	{
		$this->validatedFields[$field] = true;

		$confirm = $field . '_confirmation';

		if ($this->value($field) !== $this->value($confirm)) {
			$this->error($field, 'Confirmation mismatch.');
		}

		return $this;
	}

    public function sometimes(string $field, callable $callback): self
    {
        if ($callback($this->data)) {
            return $this->required($field);
        }
        return $this;
    }

    public function custom(callable $callback): self
    {
        $callback($this);
        return $this;
    }
}
