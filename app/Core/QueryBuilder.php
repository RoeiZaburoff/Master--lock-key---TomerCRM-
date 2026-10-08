<?php
declare(strict_types=1);

namespace App\Core;

use PDO;

class QueryBuilder
{
    private PDO $pdo;
    private string $table = '';
    private string $selectClause = '*';
    private array $conditions = [];
    private array $bindings = [];
    private array $joins = [];
    private string $orderByClause = '';
    private ?int $limitClause = null;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    public function table(string $table): static
    {
        $this->table        = $table;
        $this->selectClause = '*';
        $this->conditions   = [];
        $this->bindings     = [];
        $this->joins        = [];
        $this->orderByClause = '';
        $this->limitClause  = null;
        return $this;
    }

    public function select(string $select): static
    {
        $this->selectClause = $select;
        return $this;
    }

    public function where(string $column, mixed $value, string $operator = '='): static
    {
        $key = ':w_' . str_replace(['.', '-'], '_', $column) . count($this->conditions);
        $this->conditions[] = "$column $operator $key";
        $this->bindings[$key] = $value;
        return $this;
    }

    public function whereIn(string $column, array $values): static
    {
        $keys = [];
        foreach ($values as $i => $value) {
            $key = ':win_' . str_replace('.', '_', $column) . $i;
            $keys[] = $key;
            $this->bindings[$key] = $value;
        }
        $this->conditions[] = "$column IN (" . implode(', ', $keys) . ")";
        return $this;
    }

    public function join(string $table, string $on): static
    {
        $this->joins[] = "JOIN $table ON $on";
        return $this;
    }

    public function leftJoin(string $table, string $on): static
    {
        $this->joins[] = "LEFT JOIN $table ON $on";
        return $this;
    }

    public function orderBy(string $column, string $direction = 'ASC'): static
    {
        $this->orderByClause = "ORDER BY $column $direction";
        return $this;
    }

    public function limit(int $limit): static
    {
        $this->limitClause = $limit;
        return $this;
    }

    public function get(): array
    {
        $stmt = $this->pdo->prepare($this->buildSelect());
        $stmt->execute($this->bindings);
        return $stmt->fetchAll();
    }

    public function first(): array|null
    {
        $this->limit(1);
        $rows = $this->get();
        return $rows[0] ?? null;
    }

    public function count(): int
    {
        $this->selectClause = 'COUNT(*) as cnt';
        $row = $this->first();
        return (int) ($row['cnt'] ?? 0);
    }

    public function insert(array $data): int
    {
        $cols   = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        $stmt = $this->pdo->prepare("INSERT INTO {$this->table} ($cols) VALUES ($placeholders)");
        $stmt->execute($data);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(array $data): bool
    {
        $sets = [];
        $params = [];
        foreach ($data as $col => $val) {
            $sets[]        = "$col = :upd_$col";
            $params[":upd_$col"] = $val;
        }
        $where = $this->buildWhere();
        $sql   = "UPDATE {$this->table} SET " . implode(', ', $sets) . $where;
        $stmt  = $this->pdo->prepare($sql);
        return $stmt->execute(array_merge($params, $this->bindings));
    }

    public function delete(): bool
    {
        $sql  = "DELETE FROM {$this->table}" . $this->buildWhere();
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($this->bindings);
    }

    public function raw(string $sql, array $bindings = []): array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($bindings);
        return $stmt->fetchAll();
    }

    public function rawFirst(string $sql, array $bindings = []): array|null
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($bindings);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function rawExecute(string $sql, array $bindings = []): bool
    {
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($bindings);
    }

    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->pdo->commit();
    }

    public function rollBack(): bool
    {
        return $this->pdo->rollBack();
    }

    private function buildSelect(): string
    {
        return sprintf(
            'SELECT %s FROM %s %s%s %s %s',
            $this->selectClause,
            $this->table,
            implode(' ', $this->joins),
            $this->buildWhere(),
            $this->orderByClause,
            $this->limitClause !== null ? "LIMIT {$this->limitClause}" : ''
        );
    }

    private function buildWhere(): string
    {
        return empty($this->conditions) ? '' : ' WHERE ' . implode(' AND ', $this->conditions);
    }
}
