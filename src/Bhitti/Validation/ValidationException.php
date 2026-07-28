<?php declare(strict_types=1);

namespace Bhitti\Validation;

use RuntimeException;

final class ValidationException extends RuntimeException
{
    private array $errors;

    public function __construct(array $errors)
    {
        parent::__construct('Validation failed.');
        $this->errors = $errors;
    }

    public function errors(): array
    {
        return $this->errors;
    }
}
