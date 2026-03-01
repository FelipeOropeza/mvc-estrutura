<?php

declare(strict_types=1);

namespace Core\Database;

use PDO;

class QueryBuilder
{
    protected PDO $db;
    protected string $table;
    protected string $class;

    protected array $wheres = [];
    protected array $params = [];
    protected array $joins = [];
    protected string $selects = '*';
    protected ?int $limit = null;
    protected string $orderBy = '';

    public function __construct(PDO $db, string $table, string $class)
    {
        $this->db = $db;
        $this->table = $table;
        $this->class = $class;
    }

    public function select(string $columns): self
    {
        $this->selects = $columns;
        return $this;
    }

    public function join(string $table, string $condition, string $type = 'INNER'): self
    {
        $this->joins[] = "$type JOIN $table ON $condition";
        return $this;
    }

    public function leftJoin(string $table, string $condition): self
    {
        return $this->join($table, $condition, 'LEFT');
    }

    public function where(string $column, string $operator, mixed $value = null): self
    {
        // Se a pessoa omitir o operador, assume = (igual)
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        // Sanitiza o nome do parâmetro para o PDO evitar bugs se houver pontos (ex: usuarios.id -> usuarios_id_0)
        $paramName = str_replace('.', '_', $column) . '_' . count($this->params);
        $this->wheres[] = "$column $operator :$paramName";
        $this->params[$paramName] = $value;

        return $this;
    }

    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $this->orderBy = "ORDER BY $column $direction";
        return $this;
    }

    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * Executa a query e retorna o array preenchido com Objetos da Model final.
     */
    public function get(): array
    {
        $sql = "SELECT {$this->selects} FROM {$this->table}";

        if (!empty($this->joins)) {
            $sql .= ' ' . implode(' ', $this->joins);
        }

        if (!empty($this->wheres)) {
            $sql .= " WHERE " . implode(' AND ', $this->wheres);
        }

        if ($this->orderBy !== '') {
            $sql .= ' ' . $this->orderBy;
        }

        if ($this->limit !== null) {
            $sql .= " LIMIT {$this->limit}";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($this->params);

        return $stmt->fetchAll(PDO::FETCH_CLASS, $this->class);
    }

    /**
     * Busca o primeiro registro que bater com a query ou null.
     */
    public function first(): ?object
    {
        $this->limit(1);
        $results = $this->get();
        return $results[0] ?? null;
    }
}
