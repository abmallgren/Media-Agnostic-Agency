<?php

declare(strict_types=1);

final class FakePdo extends PDO
{
    private array $queries = [];
    private array $results = [];
    private int $lastInsertId = 0;

    public function __construct() {}

    public function setResultForQuery(string $sql, $result): void
    {
        $this->results[$sql] = $result;
    }

    public function setLastInsertId(int $id): void
    {
        $this->lastInsertId = $id;
    }

    public function lastInsertId($name = null): string|false
    {
        return (string) $this->lastInsertId;
    }

    public function prepare($sql, $options = []): PDOStatement|false
    {
        $this->queries[] = $sql;
        $result = $this->results[$sql] ?? null;

        return new class($result) extends PDOStatement {
            private mixed $result;

            public function __construct($result)
            {
                $this->result = $result;
            }

            public function execute($params = null): bool
            {
                return true;
            }

            public function fetch($mode = null, $cursorOrientation = null, $cursorOffset = null): mixed
            {
                return $this->result;
            }

            public function fetchAll($mode = null, ...$args): array
            {
                return is_array($this->result) ? $this->result : [];
            }

            public function fetchColumn($column = 0): mixed
            {
                return $this->result;
            }
        };
    }

    public function query($sql, $mode = null, ...$args): PDOStatement|false
    {
        $result = $this->results[$sql] ?? [];

        return new class($result) extends PDOStatement {
            private array $result;

            public function __construct(array $result)
            {
                $this->result = $result;
            }

            public function fetchAll($mode = null, ...$args): array
            {
                return $this->result;
            }

            public function fetchColumn($column = 0): mixed
            {
                // For count queries we store [1] or similar
                if ($this->result === []) {
                    return null;
                }

                // If associative, return first value; if indexed, use index 0
                $first = reset($this->result);
                return $first;
            }
        };
    }
}