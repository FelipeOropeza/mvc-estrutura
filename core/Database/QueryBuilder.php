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
    protected array $orWheres = [];
    protected array $params = [];
    protected array $joins = [];
    protected bool $withTrashedValue = false;
    protected bool $onlyTrashedValue = false;
    protected string $selects = '*';
    protected ?int $limit = null;
    protected ?int $offset = null;
    protected string $orderBy = '';
    protected string $groupBy = '';
    protected string $having = '';
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

    public function orWhere(string $column, string $operator, mixed $value = null): self
    {
        if ($value === null) {
            $value   = $operator;
            $operator = '=';
        }

        $paramName = str_replace('.', '_', $column) . '_or_' . count($this->params);
        $this->orWheres[] = "$column $operator :$paramName";
        $this->params[$paramName] = $value;

        return $this;
    }

    public function orWhereIn(string $column, array $values): self
    {
        if (empty($values)) {
            return $this;
        }

        $placeholders = [];
        foreach ($values as $index => $value) {
            $paramName = str_replace('.', '_', $column) . '_orin_' . count($this->params) . '_' . $index;
            $placeholders[] = ":$paramName";
            $this->params[$paramName] = $value;
        }

        $this->orWheres[] = "$column IN (" . implode(', ', $placeholders) . ")";

        return $this;
    }

    public function withTrashed(): self
    {
        $this->withTrashedValue = true;
        // Se estiver nos wheres o 'deleted_at IS NULL', removemos
        $this->wheres = array_filter($this->wheres, function($w) {
            return !str_contains($w, 'deleted_at IS NULL');
        });
        return $this;
    }

    public function onlyTrashed(): self
    {
        $this->onlyTrashedValue = true;
        $this->withTrashed();
        $this->whereNotNull("{$this->table}.deleted_at");
        return $this;
    }

    public function groupBy(string $column): self
    {
        $this->groupBy = "GROUP BY $column";
        return $this;
    }

    public function having(string $condition): self
    {
        $this->having = "HAVING $condition";
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

    public function offset(int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }

    /**
     * Monta a cláusula WHERE consolidando AND e OR corretamente.
     */
    protected function buildWhere(): string
    {
        if (empty($this->wheres) && empty($this->orWheres)) {
            return '';
        }

        $andPart = implode(' AND ', $this->wheres);
        $orPart  = implode(' OR ', $this->orWheres);

        if ($andPart && $orPart) {
            // Agrupa o OR para não vazar sem o AND
            return " WHERE ($andPart) OR ($orPart)";
        }

        return ' WHERE ' . ($andPart ?: $orPart);
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

        $sql .= $this->buildWhere();

        if ($this->groupBy !== '') {
            $sql .= ' ' . $this->groupBy;
        }

        if ($this->having !== '') {
            $sql .= ' ' . $this->having;
        }

        if ($this->orderBy !== '') {
            $sql .= ' ' . $this->orderBy;
        }

        if ($this->limit !== null) {
            $sql .= " LIMIT {$this->limit}";
        }

        if ($this->offset !== null) {
            $sql .= " OFFSET {$this->offset}";
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
     * Pagina os resultados retornando dados + metadados de paginação.
     *
     * @param int $perPage Registros por página
     * @param int $page    Página atual (padrão: lida de ?page= na URL)
     * @return array{data: array, total: int, per_page: int, current_page: int, last_page: int, from: int, to: int}
     */
    public function paginate(int $perPage = 15, ?int $page = null): array
    {
        $page = $page ?? max(1, (int) ($_GET['page'] ?? 1));

        $total = $this->count();

        $results = $this
            ->limit($perPage)
            ->offset(($page - 1) * $perPage)
            ->get();

        $lastPage = (int) ceil($total / $perPage);
        $from     = $total > 0 ? ($page - 1) * $perPage + 1 : 0;
        $to       = min($page * $perPage, $total);

        return [
            'data'         => $results,
            'total'        => $total,
            'per_page'     => $perPage,
            'current_page' => $page,
            'last_page'    => max(1, $lastPage),
            'from'         => $from,
            'to'           => $to,
        ];
    }

    /**
     * Retorna a contagem de registros baseada nos filtros aplicados.
     */
    public function count(string $column = '*'): int
    {
        $sql = "SELECT COUNT($column) FROM {$this->table}";

        if (!empty($this->joins)) {
            $sql .= ' ' . implode(' ', $this->joins);
        }

        $sql .= $this->buildWhere();

        if ($this->groupBy !== '') {
            $sql .= ' ' . $this->groupBy;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($this->params);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Executa um DELETE condicional baseado nos filtros WHERE encadeados.
     * Ex: $model->where('usuario_id', '=', 1)->where('id', '=', 5)->delete();
     */
    public function delete(): bool
    {
        $sql = "DELETE FROM {$this->table}";
        $sql .= $this->buildWhere();

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($this->params);
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
            } elseif ($def->type === 'hasOne') {
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
                    $dictionary[$r->{$def->foreignKey}] = $r;
                }

                foreach ($models as $m) {
                    $val = $m->{$def->localKey};
                    $m->setRelation($relationMethod, $dictionary[$val] ?? null);
                }
            }
        }

        return $models;
    }
}
