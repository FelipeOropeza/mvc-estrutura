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
    protected array $with = [];

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

    public function with(string|array $relations): self
    {
        $this->with = is_array($relations) ? $relations : func_get_args();
        return $this;
    }

    public function whereIn(string $column, array $values): self
    {
        if (empty($values)) {
            $this->wheres[] = "1 = 0";
            return $this;
        }

        $placeholders = [];
        foreach ($values as $index => $value) {
            $paramName = str_replace('.', '_', $column) . '_in_' . count($this->params) . '_' . $index;
            $placeholders[] = ":$paramName";
            $this->params[$paramName] = $value;
        }

        $phStr = implode(', ', $placeholders);
        $this->wheres[] = "$column IN ($phStr)";

        return $this;
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

    public function whereNull(string $column): self
    {
        $this->wheres[] = "$column IS NULL";
        return $this;
    }

    public function whereNotNull(string $column): self
    {
        $this->wheres[] = "$column IS NOT NULL";
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

        $results = $stmt->fetchAll(PDO::FETCH_CLASS, $this->class);

        if (!empty($results) && !empty($this->with)) {
            $results = $this->eagerLoadRelations($results);
        }

        return $results;
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

    protected function eagerLoadRelations(array $models): array
    {
        $first = $models[0];

        // ⚠️ Nota: eagerLoadRelations suporta apenas belongsTo e hasMany no momento.
        // O suporte para hasOne não foi implementado e será ignorado se tentado.
        foreach ($this->with as $relationMethod) {
            if (!method_exists($first, $relationMethod)) {
                continue;
            }

            $def = $first->getRelationDefinition($relationMethod);
            if (!$def instanceof RelationDefinition) {
                continue;
            }

            if ($def->type === 'belongsTo') {
                $ids = [];
                foreach ($models as $m) {
                    $val = $m->{$def->foreignKey};
                    if ($val !== null && !in_array($val, $ids)) {
                        $ids[] = $val;
                    }
                }

                if (empty($ids)) continue;

                $relatedModels = (new $def->relatedClass())->whereIn($def->localKey, $ids)->get();

                $dictionary = [];
                foreach ($relatedModels as $r) {
                    $dictionary[$r->{$def->localKey}] = $r;
                }

                foreach ($models as $m) {
                    $val = $m->{$def->foreignKey};
                    $m->setRelation($relationMethod, $dictionary[$val] ?? null);
                }
            } elseif ($def->type === 'hasMany') {
                $ids = [];
                foreach ($models as $m) {
                    $val = $m->{$def->localKey};
                    if ($val !== null && !in_array($val, $ids)) {
                        $ids[] = $val;
                    }
                }

                if (empty($ids)) continue;

                $relatedModels = (new $def->relatedClass())->whereIn($def->foreignKey, $ids)->get();

                $dictionary = [];
                foreach ($relatedModels as $r) {
                    $dictionary[$r->{$def->foreignKey}][] = $r;
                }

                foreach ($models as $m) {
                    $val = $m->{$def->localKey};
                    $m->setRelation($relationMethod, $dictionary[$val] ?? []);
                }
            }
        }

        return $models;
    }
}
