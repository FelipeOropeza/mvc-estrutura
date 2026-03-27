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
    protected array $orderBy = [];
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

    public function from(string $table): self
    {
        $this->table = $table;
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

    public function with(string|array $relations, string ...$extra): self
    {
        if (is_array($relations)) {
            $this->with = $relations;
        } else {
            $this->with = [$relations, ...$extra];
        }
        return $this;
    }

    public function whereIn(string $column, array|\Closure $values): self
    {
        if ($values instanceof \Closure) {
            // NOTA: A subquery herda a tabela e a classe da query principal por padrão. 
            // Se precisar buscar em outra tabela, use $subQuery->from('outra_tabela').
            $subQuery = new self($this->db, $this->table, $this->class);
            $values($subQuery);
            
            // Aqui buscamos a parte do SELECT do sub-objeto para montar a query aninhada
            $sql = "SELECT {$subQuery->selects} FROM {$subQuery->table}";
            $sql .= $subQuery->buildWhere();
            
            $this->wheres[] = "$column IN ($sql)";
            $this->params = array_merge($this->params, $subQuery->params);
            return $this;
        }

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

    public function where(string|\Closure $column, ?string $operator = null, mixed $value = null): self
    {
        if ($column instanceof \Closure) {
            $subQuery = new self($this->db, $this->table, $this->class);
            $column($subQuery);
            $this->wheres[] = '(' . $subQuery->buildRawWhere() . ')';
            $this->params = array_merge($this->params, $subQuery->params);
            return $this;
        }

        // Se a pessoa omitir o operador, assume = (igual)
        if ($value === null && $operator !== null) {
            $value = $operator;
            $operator = '=';
        }

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

    public function orWhere(string|\Closure $column, ?string $operator = null, mixed $value = null): self
    {
        if ($column instanceof \Closure) {
            $subQuery = new self($this->db, $this->table, $this->class);
            $column($subQuery);
            $this->orWheres[] = '(' . $subQuery->buildRawWhere() . ')';
            $this->params = array_merge($this->params, $subQuery->params);
            return $this;
        }

        if ($value === null && $operator !== null) {
            $value   = $operator;
            $operator = '=';
        }

        $paramName = str_replace('.', '_', $column) . '_or_' . count($this->params);
        $this->orWheres[] = "$column $operator :$paramName";
        $this->params[$paramName] = $value;

        return $this;
    }

    /**
     * Retorna a parte bruta do WHERE (sem a palavra chave WHERE)
     * Utilizado internamente para agrupamento de filtros.
     */
    public function buildRawWhere(): string
    {
        $andPart = implode(' AND ', $this->wheres);
        $orPart  = implode(' OR ', $this->orWheres);

        if ($andPart && $orPart) {
            return "($andPart) OR ($orPart)";
        }

        return $andPart ?: $orPart;
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
        $this->orderBy[] = "$column $direction";
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

        if (!empty($this->orderBy)) {
            $sql .= ' ORDER BY ' . implode(', ', $this->orderBy);
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
        if (empty($this->wheres) && empty($this->orWheres)) {
            throw new \LogicException(
                "Operação bloqueada: delete() chamado sem nenhuma cláusula WHERE na tabela '{$this->table}'. "
                . 'Adicione ao menos um where() para continuar.'
            );
        }

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

    /**
     * Busca um registro pelo ID.
     */
    public function find(mixed $id): ?object
    {
        return $this->where('id', '=', $id)->first();
    }


    protected function eagerLoadRelations(array $models): array
    {
        if (empty($models)) return $models;
        
        $first = $models[0];

        foreach ($this->with as $key => $value) {
            $fullPath = is_string($key) ? $key : (is_string($value) ? $value : null);
            $closure = is_string($key) ? $value : null;

            if (!$fullPath) continue;

            // Suporte a relações aninhadas via ponto: 'atleta.equipe'
            $parts = explode('.', $fullPath);
            $relationMethod = $parts[0];
            $remaining = count($parts) > 1 ? implode('.', array_slice($parts, 1)) : null;

            if (!method_exists($first, $relationMethod)) {
                continue;
            }

            $def = $first->getRelationDefinition($relationMethod);
            if (!$def instanceof RelationDefinition) {
                continue;
            }

            // Coletar IDs da model atual para buscar as relacionadas
            $ids = [];
            $sourceKey = ($def->type === 'belongsTo') ? $def->foreignKey : $def->localKey;
            $targetKey = ($def->type === 'belongsTo') ? $def->localKey : $def->foreignKey;

            foreach ($models as $m) {
                $val = $m->{$sourceKey};
                if ($val !== null && !in_array($val, $ids)) {
                    $ids[] = $val;
                }
            }

            if (empty($ids)) continue;

            $query = (new $def->relatedClass())->newQuery();
            $query->whereIn($targetKey, $ids);

            // Se for o último nível do path e houver closure, aplica
            if (!$remaining && $closure instanceof \Closure) {
                $closure($query);
            }

            // Se ainda houver níveis (ex: 'equipe' de 'atleta.equipe'), passa adiante
            if ($remaining) {
                $query->with($remaining);
            }

            $relatedModels = $query->get();

            // Mapeamento dos resultados
            $dictionary = [];
            foreach ($relatedModels as $r) {
                $k = $r->{$targetKey};
                if ($def->type === 'hasMany') {
                    $dictionary[$k][] = $r;
                } else {
                    $dictionary[$k] = $r;
                }
            }

            // Vínculo das relações nos objetos originais
            foreach ($models as $m) {
                $val = $m->{$sourceKey};
                if ($def->type === 'hasMany') {
                    $m->setRelation($relationMethod, $dictionary[$val] ?? []);
                } else {
                    $m->setRelation($relationMethod, $val !== null ? ($dictionary[$val] ?? null) : null);
                }
            }
        }

        return $models;
    }
}
