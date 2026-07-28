<?php

declare(strict_types=1);

namespace Bhitti\Config;

final class Env
{
    public function get(string $key, mixed $default = null): mixed
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

        if ($value === false || $value === null) {
            return value($default);
        }

        if (!is_string($value)) {
            return $value;
        }

        $value = trim($value);

        return match (strtolower($value)) {
            'true', '(true)'   => true,
            'false', '(false)' => false,
            'null', '(null)'   => null,
            'empty', '(empty)' => '',
            default            => $this->castScalar($value),
        };
    }

    private function castScalar(string $value): mixed
    {
        if ($value === '') {
            return '';
        }

        $first = $value[0];
        $last = $value[strlen($value) - 1];

        if (($first === '"' && $last === '"')
            || ($first === "'" && $last === "'")) {
            return substr($value, 1, -1);
        }

        if (preg_match('/^-?\d+$/', $value)) {
            return (int) $value;
        }

        if (preg_match('/^-?\d+\.\d+$/', $value)) {
            return (float) $value;
        }

        return $value;
    }
}