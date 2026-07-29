<?php

namespace Bhitti\Database;

use Bhitti\Database\QueryBuilder;

// Framework Core Model. Do not remove this
class DB extends QueryBuilder
{
    public function beginTransaction(): bool
    {
        return $this->database->beginTransaction($this->connectionName);
    }

    public function commit(): bool
    {
        return $this->database->commit($this->connectionName);
    }

    public function rollBack(): bool
    {
        return $this->database->rollBack($this->connectionName);
    }

    public function inTransaction(): bool
    {
        return $this->database->inTransaction($this->connectionName);
    }

    public function transaction(callable $callback): mixed
    {
        return $this->database->transaction($callback, $this->connectionName);
    }
}
