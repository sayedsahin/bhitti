<?php

declare(strict_types=1);

namespace Bhitti\Database;

use InvalidArgumentException;
use PDO;

/**
 * Lightweight SQL Query Builder.
 *
 * Terminal methods execute the query and reset the current query state.
 */
abstract class QueryBuilder
{
    protected PDO $pdo;
    private string $driver;

    // Transaction
    protected Database $database;
    protected ?string $connectionName = null;

    // Model Property
    protected string $defaultTable = '';
    protected array $defaultSelect = ['*'];
    protected ?string $defaultConnection = null;

    private string $table;
    private array $select;
    private array $joins = [];
    private array $wheres = [];
    private array $bindings = [];
    private string $order = '';
    private string $limit = '';

    private int $paramCounter = 0;

    // Full raw SQL mode (builder bypass)
    private ?string $rawSql = null;

    /**
     * Create a new QueryBuilder instance.
     *
     * Example 1: `db()->table('users')->get()`
     *
     * When Extend QueryBuilder by Model
     * Example 2: `$model = new User(); $model->select('id', 'email')->get()`
     *
     * Example 3: `$query = new QueryBuilder($database);`
     */
    public function __construct(?Database $db = null, ?string $connection = null)
    {
        $this->table = $this->defaultTable;
        $this->select = $this->defaultSelect;

        if ($db === null) {
            global $container;
            $db = $container->make(Database::class);
        }

        $connection ??= $this->defaultConnection;

        $this->database = $db;
        $this->connectionName = $connection;

        $this->pdo = $db->connection($connection);
        $this->driver = $db->driver($connection);
    }


    /* ============================================================
       RAW SQL (FULL QUERY MODE)
    ============================================================ */

    /**
     * Set a complete raw SQL query with optional bindings.
     *
     * The SQL string must always be developer-controlled.
     *
     * Example 1: `->raw('SELECT * FROM users WHERE email = ? ORDER BY id DESC LIMIT 1', [$email])->first()`
     * Example 2: `->raw('UPDATE users SET status = ? WHERE id = ?', ['active', 5])->execute()`
     * Example 3: `->raw('INSERT INTO users (name, email) VALUES (?, ?)', [$name, $email])->execute(true)`
     */
    public function raw(string $sql, array $bindings = []): self
    {
        $this->reset();

        $this->rawSql = $sql;
        $this->bindings = $bindings;

        return $this;
    }

    /**
     * Create a new model query.
     *
     * Example 1: `User::query()->select('id', 'email')->get()`
     */
    public static function query(?string $connection = null): static
    {
        global $container;

        return new static(
            $container->make(Database::class),
            $connection
        );
    }


    /* ============================================================
       CHAINING
    ============================================================ */

    /**
     * Set the table used by the query.
     *
     * Example 1: `->table('users')`
     */
    public function table(string $table): self
    {
        $this->table = $table;

        return $this;
    }

    /**
     * Set the columns returned by the query.
     *
     * Example 1: `->select('id', 'name', 'email')->get()`
     */
    public function select(string ...$columns): self
    {
        $this->select = $columns ?: ['*'];

        return $this;
    }


    /* ============================================================
       BASIC WHERE
    ============================================================ */

    /**
     * Add a WHERE condition.
     *
     * Example 1: `->where('id', 5)`
     * Example 2: `->where('created_at', '>=', $date)`
     *
     * use whereNull()/whereNotNull() for `WHERE IS NULL/NOT NULL`
     */
    public function where(string $column, $operator = null, $value = null, string $boolean = 'AND'): self
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        $param = $this->param();
        $this->bindings[$param] = $value;

        $this->wheres[] = [
            'boolean' => $boolean,
            'sql' => "$column $operator $param",
        ];

        return $this;
    }

    /**
     * Add an OR WHERE condition.
     *
     * Example 1: `->where('status', 'active')->orWhere('role', '=', 'admin')`
     */
    public function orWhere(string $column, $operator = null, $value = null): self
    {
        if (func_num_args() === 2) {
            return $this->where($column, '=', $operator, 'OR');
        }
        return $this->where($column, $operator, $value, 'OR');
    }


    /* ============================================================
       RAW WHERE EXPRESSIONS

       Values are bound safely, but the SQL expression itself
       must always be developer-controlled.
    ============================================================ */

    /**
     * Add a raw WHERE expression with bound values.
     *
     * Example 1: `->whereRaw('YEAR(created_at) = ?', [2026])`
     */
    public function whereRaw(string $sql, array $bindings = [], string $boolean = 'AND'): self
    {
        if (substr_count($sql, '?') !== count($bindings)) {
            throw new InvalidArgumentException(
                'Raw WHERE placeholders and bindings count must match.'
            );
        }
        $index = 0;

        $sql = preg_replace_callback('/\?/', function () use (&$index, $bindings) {
            $param = $this->param();
            $this->bindings[$param] = $bindings[$index++];

            return $param;
        }, $sql);

        $this->wheres[] = [
            'boolean' => $boolean,
            'sql' => $sql,
        ];

        return $this;
    }

    /**
     * Add a raw OR WHERE expression with bound values.
     *
     * Example 1: `->orWhereRaw('LOWER(email) = ?', [strtolower($email)])`
     */
    public function orWhereRaw(string $sql, array $bindings = []): self
    {
        return $this->whereRaw($sql, $bindings, 'OR');
    }


    /* ============================================================
       NULL CHECK
    ============================================================ */

    /**
     * Add an IS NULL condition.
     *
     * Example 1: `->whereNull('deleted_at')->get()`
     */
    public function whereNull(string $column, string $boolean = 'AND'): self
    {
        $this->wheres[] = ['boolean' => $boolean, 'sql' => "$column IS NULL"];

        return $this;
    }

    /**
     * Add an IS NOT NULL condition.
     *
     * Example 1: `->whereNotNull('email_verified_at')->get()`
     */
    public function whereNotNull(string $column, string $boolean = 'AND'): self
    {
        $this->wheres[] = ['boolean' => $boolean, 'sql' => "$column IS NOT NULL"];

        return $this;
    }


    /* ============================================================
       LIKE
    ============================================================ */

    /**
     * Add a LIKE condition.
     *
     * Example 1: `->like('name', '%John Doe%')->get()`
     */
    public function like(string $column, string $value, string $boolean = 'AND'): self
    {
        $param = $this->param();
        $this->bindings[$param] = $value;

        $this->wheres[] = ['boolean' => $boolean, 'sql' => "$column LIKE $param"];

        return $this;
    }


    /* ============================================================
       JOIN
    ============================================================ */

    /**
     * Add a JOIN clause.
     *
     * Example 1: `->join('roles', 'roles.id', '=', 'users.role_id')`
     */
    public function join(string $table, string $first, string $operator, string $second, string $type = 'INNER'): self
    {
        $this->joins[] = "$type JOIN $table ON $first $operator $second";

        return $this;
    }

    /**
     * Add an INNER JOIN clause.
     *
     * Example 1: `->innerJoin('roles', 'roles.id', '=', 'users.role_id')`
     */
    public function innerJoin(string $table, string $first, string $operator, string $second): self
    {
        return $this->join($table, $first, $operator, $second, 'INNER');
    }

    /**
     * Add a LEFT JOIN clause.
     *
     * Example 1: `->leftJoin('profiles', 'profiles.user_id', '=', 'users.id')`
     */
    public function leftJoin(string $table, string $first, string $operator, string $second): self
    {
        return $this->join($table, $first, $operator, $second, 'LEFT');
    }


    /* ============================================================
       ORDER & LIMIT
    ============================================================ */

    /**
     * Set the ORDER BY expression.
     *
     * The expression must always be developer-controlled.
     *
     * Example 1: `->order('created_at DESC')->get()`
     * Example 2: `->order('Country ASC, CustomerName DESC')->get()`
     */
    public function order(string $order): self
    {
        $this->order = " ORDER BY $order";

        return $this;
    }

    /**
     * Limit the number of returned rows with an optional offset.
     *
     * Example 1: `->limit(10)->get()`
     * Example 2: `->limit(10, 20)->get()`
     */
    public function limit(int $limit, ?int $offset = null): self
    {
        $this->limit = " LIMIT $limit";

        if ($offset !== null) {
            $this->limit .= " OFFSET $offset";
        }

        return $this;
    }


    /* ============================================================
       EXECUTION
    ============================================================ */

    /**
     * Execute the query and return all matching rows.
     *
     * Example 1: `->table('users')->where('status', 'active')->get()`
     *
     * @return array<int, object>
     */
    public function get(): array
    {
        try {
            if ($this->rawSql) {
                $stmt = $this->pdo->prepare($this->rawSql);
                $stmt->execute($this->bindings);
                $data = $stmt->fetchAll(PDO::FETCH_OBJ);

                return $data;
            }

            $sql = $this->toSql();
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($this->bindings);

            $data = $stmt->fetchAll(PDO::FETCH_OBJ);

            return $data;
        } finally {
            $this->reset();
        }
    }

    /**
     * Execute the query and return the first matching row.
     *
     * Example 1: `->table('users')->where('email', $email)->first()`
     */
    public function first(): ?object
    {
        try {
            $this->limit = ' LIMIT 1'; //not for raw sql
            $sql = $this->rawSql ?: $this->toSql();

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($this->bindings);

            $result = $stmt->fetch(PDO::FETCH_OBJ) ?: null;

            return $result;
        } finally {
            $this->reset();
        }
    }

    /**
     * Find a row using an ID or another unique column.
     *
     * Example 1: `->table('users')->find(5)`
     * Example 2: `->table('users')->find(10, 'user_id')`
     */
    public function find(int|string $id, string $column = 'id'): ?object
    {
        return $this->where($column, $id)->first();
    }

    /**
     * Determine whether a matching row exists.
     *
     * Example 1: `->table('users')->where('email', $email)->exists()`
     */
    public function exists(): bool
    {
        try {
            if ($this->rawSql) {
                $rawSql = rtrim($this->rawSql, " \t\n\r\0\x0B;");
                $sql = "SELECT EXISTS({$rawSql})";
            } else {
                $sql = "SELECT EXISTS(SELECT 1 FROM {$this->table}"
                    . $this->compileJoins()
                    . $this->compileWheres()
                    . ")";
            }

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($this->bindings);

            return (bool) $stmt->fetchColumn();
        } finally {
            $this->reset();
        }
    }

    /**
     * Count matching rows or values from a column.
     *
     * Example 1: `->table('users')->count()`
     * Example 2: `->table('users')->where('status', 'active')->count('id')`
     */
    public function count(string $column = '*'): int
    {
        try {
            if ($this->rawSql) {
                $rawSql = rtrim($this->rawSql, " \t\n\r\0\x0B;");
                $sql = "SELECT COUNT(*) AS total FROM ({$rawSql}) AS sub";
            } else {
                $sql = "SELECT COUNT({$column}) AS total FROM {$this->table}"
                    . $this->compileJoins()
                    . $this->compileWheres();
            }

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($this->bindings);

            $total = (int) $stmt->fetchColumn();

            return $total;
        } finally {
            $this->reset();
        }
    }


    /* ============================================================
       INSERT / UPDATE / DELETE
    ============================================================ */

    /**
     * Insert a new row and optionally return the inserted ID.
     *
     * Example 1: `->table('users')->insert(['name' => $name])`
     * Example 2: `->table('users')->insert(['name' => $name], true)`
     * Example 3: `->table('users')->insert($data, true, 'user_id')`
     *
     */
    public function insert(array $data, bool $returnId = false, string $primaryKey = 'id'): bool|int
    {
        try {
            if ($data === []) {
                throw new InvalidArgumentException('Insert data cannot be empty.');
            }

            $cols = array_keys($data);
            $placeholders = [];

            foreach ($data as $col => $val) {
                $p = $this->param();
                $this->bindings[$p] = $val;
                $placeholders[] = $p;
            }

            $sql = "INSERT INTO {$this->table} ("
                . implode(', ', $cols)
                . ") VALUES ("
                . implode(', ', $placeholders)
                . ")";

            if ($returnId && $this->driver === 'pgsql') {
                $sql .= " RETURNING {$primaryKey}";
            }

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($this->bindings);

            if (!$returnId) {
                return true;
            }

            return $this->driver === 'pgsql'
                ? (int) $stmt->fetchColumn()
                : (int) $this->pdo->lastInsertId();
        } finally {
            $this->reset();
        }
    }

    /**
     * Update rows matching the current WHERE conditions.
     *
     * A WHERE condition is required.
     *
     * Example 1: `->table('users')->where('id', 5)->update(['status' => 'active'])`
     */
    public function update(array $data): bool
    {
        try {
            if (empty($this->wheres)) {
                throw new InvalidArgumentException("UPDATE without WHERE is forbidden!");
            }

            if ($data === []) {
                throw new InvalidArgumentException('Update data cannot be empty.');
            }

            $set = [];

            foreach ($data as $col => $val) {
                $p = $this->param();
                $this->bindings[$p] = $val;
                $set[] = "$col = $p";
            }

            $sql = "UPDATE {$this->table} SET "
                . implode(', ', $set)
                . $this->compileWheres();

            $stmt = $this->pdo->prepare($sql);
            $ok = $stmt->execute($this->bindings);

            return $ok;
        } finally {
            $this->reset();
        }
    }

    /**
     * Update a matching row or insert a new row when no match exists.
     *
     * Example 1: `->table('users')->updateOrInsert(['email' => $email], ['name' => $name])`
     *
     */
    public function updateOrInsert(array $search, array $data = []): bool|int
    {
        if ($search === []) {
            throw new InvalidArgumentException('Search conditions cannot be empty.');
        }

        $checker = clone $this;
        $checker->whereConditions($search);

        if ($checker->exists()) {
            $this->whereConditions($search);
            $this->update($data ?: $search);

            return true;
        }

        $insertData = array_merge($data, $search);

        return $this->insert($insertData, true);
    }

    /**
     * Delete rows matching the current WHERE conditions.
     *
     * A WHERE condition is required.
     *
     * Example 1: `->table('users')->where('id', 5)->delete()`
     */
    public function delete(): bool
    {
        try {
            if (empty($this->wheres)) {
                throw new InvalidArgumentException("DELETE without WHERE is forbidden!");
            }

            $sql = "DELETE FROM {$this->table}" . $this->compileWheres();

            $stmt = $this->pdo->prepare($sql);
            $ok = $stmt->execute($this->bindings);

            return $ok;
        } finally {
            $this->reset();
        }
    }

    /**
     * Execute a raw INSERT, UPDATE or DELETE query.
     *
     * Example 1: `->raw('UPDATE users SET status = ? WHERE id = ?', ['active', 5])->execute()`
     * Example 2: `->raw('INSERT INTO users (name) VALUES (?)', [$name])->execute(true)`
     * Example 3: `->raw('INSERT INTO users (name) VALUES (?) RETURNING id', [$name])->execute(true)`
     *
     */
    public function execute(bool $returnId = false): bool|int
    {
        try {
            if (!$this->rawSql) {
                throw new InvalidArgumentException('No raw SQL query has been set.');
            }

            $stmt = $this->pdo->prepare($this->rawSql);
            $success = $stmt->execute($this->bindings);

            if (!$returnId) {
                return $success;
            }

            if ($this->driver === 'pgsql') {
                $id = $stmt->fetchColumn();

                if ($id === false) {
                    throw new InvalidArgumentException(
                        'PostgreSQL raw insert must include a RETURNING column.'
                    );
                }

                return (int) $id;
            }

            return (int) $this->pdo->lastInsertId();
        } finally {
            $this->reset();
        }
    }

    /**
     * Return all values from one column.
     *
     * Example 1: `->table('users')->where('status', 'active')->pluck('email')`
     *
     * @return array<int, mixed>
     */
    public function pluck(string $column): array
    {
        try {
            $this->select($column);
            $sql = $this->rawSql ?: $this->toSql();

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($this->bindings);

            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } finally {
            $this->reset();
        }
    }

    /**
     * Return one column value from the first matching row.
     *
     * Example 1: `->table('users')->where('id', 5)->value('email')`
     */
    public function value(string $column): mixed
    {
        try {
            $this->limit = ' LIMIT 1';

            $this->select($column);
            $sql = ($this->rawSql ?: $this->toSql());

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($this->bindings);

            $value = $stmt->fetchColumn();

            return $value === false ? null : $value;
        } finally {
            $this->reset();
        }
    }


    /* ============================================================
       SQL BUILDERS
    ============================================================ */

    /**
     * Compile and return the current SELECT query.
     *
     * Example 1: `->table('users')->where('status', 'active')->toSql()`
     */
    public function toSql(): string
    {
        return "SELECT " . implode(', ', $this->select)
            . " FROM {$this->table}"
            . $this->compileJoins()
            . $this->compileWheres()
            . $this->order
            . $this->limit;
    }

    /**
     * Compile the current JOIN clauses.
     */
    private function compileJoins(): string
    {
        return $this->joins ? " " . implode(" ", $this->joins) : '';
    }

    /**
     * Compile the current WHERE conditions.
     */
    private function compileWheres(): string
    {
        if (empty($this->wheres)) {
            return '';
        }

        $parts = [];

        foreach ($this->wheres as $i => $w) {
            $bool = $i === 0 ? 'WHERE' : $w['boolean'];
            $parts[] = "$bool {$w['sql']}";
        }

        return ' ' . implode(' ', $parts);
    }


    /* ============================================================
       HELPERS
    ============================================================ */

    /**
     * Generate the next named SQL parameter.
     */
    private function param(): string
    {
        return ':p' . (++$this->paramCounter);
    }

    /**
     * Add multiple equality WHERE conditions.
     *
     * Example 1: `->whereConditions(['status' => 'active', 'role_id' => 2])->get()`
     */
    public function whereConditions(array $conditions, string $boolean = 'AND'): self
    {
        foreach ($conditions as $column => $value) {
            $this->where($column, '=', $value, $boolean);
        }

        return $this;
    }


    /**
     * Reset query-specific state while preserving the selected table.
     */
    private function reset(): void
    {
        $this->table = $this->defaultTable;
        $this->select = $this->defaultSelect;

        $this->order = '';
        $this->limit = '';
        $this->rawSql = null;

        $this->joins = [];
        $this->wheres = [];
        $this->bindings = [];
        $this->paramCounter = 0;
    }
}