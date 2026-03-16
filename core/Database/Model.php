<?php

declare(strict_types=1);

namespace Core\Database;

use PDO;

#[\AllowDynamicProperties]
abstract class Model implements \JsonSerializable
{
    /** @var PDO */
    protected PDO $db;

    /** @var string|null Nome da tabela (se null, será plural do nome da classe) */
    protected ?string $table = null;

    /** @var string Nome da chave primária */
    protected string $primaryKey = 'id';

    /** @var array Lista de colunas seguras e permitidas para serem manipuladas em massa */
    protected array $fillable = [];

    /** @var array Lista de colunas que devem ser ocultadas em debugInfo, JSON e Array */
    protected array $hidden = [];

    /** @var bool Ativa/Desativa controle automático das colunas created_at e updated_at */
    public bool $timestamps = true;

    /** @var bool Ativa/Desativa a filtragem e deleção via soft deletes */
    public bool $softDeletes = false;

    public function __construct()
    {
        $this->db = Connection::getInstance();

        if ($this->table === null) {
            $classPath = explode('\\', static::class);
            $className = end($classPath);
            $this->table = pluralize(strtolower($className));
        }
    }

    /** @var array Guarda os relacionamentos do Eager Loading */
    protected array $loadedRelations = [];

    /** @var bool Flag para inspecionar método e obter a definição, usado pelo Eager Loading */
    public bool $relationDefinitionMode = false;

    /**
     * Métodos Mágicos para getters dinâmicos
     * 
     * @param string $name
     * @return mixed
     */
    public function __get(string $name): mixed
    {
        // Se a propriedade é padrão do model
        if (property_exists($this, $name)) {
            return $this->$name;
        }

        // Se o eager loading já carregou, retorna
        if (array_key_exists($name, $this->loadedRelations)) {
            return $this->loadedRelations[$name];
        }

        // Se existe um método com este nome, invoca (Lazy Loading de relações como $user->pedidos)
        if (method_exists($this, $name)) {
            $relationResult = $this->$name();
            // Apenas para ter certeza que não estamos pegando o Definition
            if (!($relationResult instanceof RelationDefinition)) {
                $this->loadedRelations[$name] = $relationResult;
                return $relationResult;
            }
        }

        return $this->$name ?? null;
    }

    /**
     * Verifica se uma relação ou propriedade existe (necessário para empty() e isset())
     */
    public function __isset(string $name): bool
    {
        return property_exists($this, $name) || 
               array_key_exists($name, $this->loadedRelations) || 
               method_exists($this, $name);
    }

    public function setRelation(string $name, mixed $value): void
    {
        $this->loadedRelations[$name] = $value;
    }

    public function getRelationDefinition(string $method): ?RelationDefinition
    {
        if (!method_exists($this, $method)) {
            return null;
        }

        $this->relationDefinitionMode = true;
        try {
            // Chama o método para interceptar os parâmetros e retornar a Definição
            $def = $this->$method();
        } finally {
            $this->relationDefinitionMode = false; // Sempre reseta independentemente de exceptions
        }

        return $def instanceof RelationDefinition ? $def : null;
    }

    /**
     * @param string $name
     * @param mixed $value
     */
    public function __set(string $name, mixed $value): void
    {
        $this->$name = $value;
    }

    /**
     * Valida os dados informados de acordo com os Atributos PHP (#[Required], etc) da Model.
     * Funciona em formato Active Record, segurando e bloqueando a Request caso inviável.
     * 
     * @param array|null $data Array assoc de dados (usará $_POST/$_GET se null)
     * @return array Array seguro de dados após passar pelas regras
     */
    public function validate(?array $data = null): array
    {
        // Se a pessoa não enviou o array pra validar, pegamos da Request global automaticamente
        $inputData = $data ?? request()->all();

        $validator = new \Core\Validation\Validator();
        $isValid = $validator->validate($this, $inputData);

        if (!$isValid) {
            $errors = $validator->getErrors();
            throw new \Core\Exceptions\ValidationException($errors, $inputData);
        }

        return $validator->getValidatedData();
    }

    /**
     * Busca todos os registros da tabela
     * 
     * @return array
     */
    public function all(): array
    {
        return $this->newQuery()->get();
    }

    /**
     * Busca um registro pelo seu ID
     * 
     * @param mixed $id
     * @return static|null
     */
    public function find(mixed $id): ?static
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id";
        
        if (property_exists($this, 'softDeletes') && $this->softDeletes) {
            $sql .= " AND deleted_at IS NULL";
        }
        
        $sql .= " LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id);
        $stmt->execute();
        $stmt->setFetchMode(PDO::FETCH_CLASS, static::class);
        $result = $stmt->fetch();

        return $result !== false ? $result : null;
    }

    /**
     * Busca um registro pelo seu ID ou lança uma HttpException 404.
     *
     * @param mixed $id
     * @return static
     * @throws \Core\Exceptions\HttpException
     */
    public function findOrFail(mixed $id): static
    {
        $result = $this->find($id);

        if ($result === null) {
            throw new \Core\Exceptions\HttpException(
                'Registro não encontrado.',
                404
            );
        }

        return $result;
    }

    /**
     * Insere um novo registro no banco de dados
     *
     * @param array $data Ex: ['nome' => 'Felipe', 'email' => 'felipe@etc.com']
     * @return int O ID inserido
     */
    public function insert(array $data): int
    {
        $data = $this->filterFillable($data);

        if ($this->timestamps && !isset($data['created_at'])) {
            $now = date('Y-m-d H:i:s');
            $data['created_at'] = $now;
            $data['updated_at'] = $now;
        }

        $columns = '`' . implode('`, `', array_keys($data)) . '`';
        // Cria os placeholders (:nome, :email)
        $placeholders = ':' . implode(', :', array_keys($data));

        $sql = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";

        $stmt = $this->db->prepare($sql);

        foreach ($data as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }

        $stmt->execute();

        return (int) $this->db->lastInsertId();
    }

    /**
     * Atualiza um registro existente
     *
     * @param mixed $id
     * @param array $data Ex: ['nome' => 'Felipe 2']
     * @return bool
     */
    public function update(mixed $id, array $data): bool
    {
        $data = $this->filterFillable($data);

        if ($this->timestamps && !isset($data['updated_at'])) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }

        $fields = [];
        foreach ($data as $key => $value) {
            // Backticks protegem nomes de colunas contra SQL injection via chave do array
            $fields[] = "`{$key}` = :{$key}";
        }
        $fieldsStr = implode(', ', $fields);

        // Usa :__pk_id para evitar conflito se $data tiver uma chave 'id'
        $sql = "UPDATE {$this->table} SET {$fieldsStr} WHERE {$this->primaryKey} = :__pk_id";

        $stmt = $this->db->prepare($sql);

        $stmt->bindValue(':__pk_id', $id);
        foreach ($data as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }

        return $stmt->execute();
    }

    /**
     * Deleta um registro pelo ID
     * 
     * @param mixed $id
     * @return bool
     */
    public function delete(mixed $id): bool
    {
        if (property_exists($this, 'softDeletes') && $this->softDeletes) {
            $sql = "UPDATE {$this->table} SET deleted_at = :deleted_at WHERE {$this->primaryKey} = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':id', $id);
            $stmt->bindValue(':deleted_at', date('Y-m-d H:i:s'));
            
            return $stmt->execute();
        }

        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id);

        return $stmt->execute();
    }

    /**
     * Retorna a query builder caso queira fazer queries customizadas no controller
     * 
     * @param string $sql
     * @param array $params
     * @return array
     */
    public function query(string $sql, array $params = []): array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Inicia o construtor de consultas avançadas (QueryBuilder).
     */
    public function newQuery(): QueryBuilder
    {
        $query = new QueryBuilder($this->db, $this->table, static::class);
        
        if (property_exists($this, 'softDeletes') && $this->softDeletes) {
            $query->whereNull("{$this->table}.deleted_at");
        }
        
        return $query;
    }

    /**
     * Retorna a contagem de registros baseada na query.
     */
    public function count(string $column = '*'): int
    {
        return $this->newQuery()->count($column);
    }

    /**
     * Inicia uma verificação fluente na Tabela
     * Ex: $produto->where('preco', '>', 50)->get();
     */
    public function where(string $column, string $operator, mixed $value = null): QueryBuilder
    {
        return $this->newQuery()->where($column, $operator, $value);
    }

    /**
     * Inclui registros deletados (Soft Deletes)
     */
    public function withTrashed(): QueryBuilder
    {
        return $this->newQuery()->withTrashed();
    }

    /**
     * Retorna apenas registros deletados (Soft Deletes)
     */
    public function onlyTrashed(): QueryBuilder
    {
        return $this->newQuery()->onlyTrashed();
    }

    /**
     * Inicia um JOIN fluente entre tabelas.
     * Ex: $produto->join('categorias', 'categorias.id = produtos.categoria_id')->get();
     */
    public function join(string $table, string $condition, string $type = 'INNER'): QueryBuilder
    {
        return $this->newQuery()->join($table, $condition, $type);
    }

    /**
     * Inicia uma verificação fluente IN na Tabela
     * Ex: $produto->whereIn('id', [1,2,3])->get();
     */
    public function whereIn(string $column, array $values): QueryBuilder
    {
        return $this->newQuery()->whereIn($column, $values);
    }

    /**
     * Inicia o Eager Loading de relações.
     */
    public function with(string|array $relations): QueryBuilder
    {
        return $this->newQuery()->with($relations);
    }

    /**
     * Ordena os resultados.
     */
    public function orderBy(string $column, string $direction = 'ASC'): QueryBuilder
    {
        return $this->newQuery()->orderBy($column, $direction);
    }

    /**
     * Limita os resultados.
     */
    public function limit(int $limit): QueryBuilder
    {
        return $this->newQuery()->limit($limit);
    }

    /**
     * Define o offset.
     */
    public function offset(int $offset): QueryBuilder
    {
        return $this->newQuery()->offset($offset);
    }

    /**
     * Pega o primeiro registro.
     */
    public function first(): ?static
    {
        return $this->newQuery()->first();
    }

    /**
     * Relacionamento 1:1 - Esta Model "Pertence A" Outra.
     * Ex: $produto->categoria() >> belongsTo(Categoria::class, 'categoria_id')
     */
    protected function belongsTo(string $relatedClass, string $foreignKey, string $ownerKey = 'id'): mixed
    {
        if ($this->relationDefinitionMode) {
            return new RelationDefinition('belongsTo', $relatedClass, $foreignKey, $ownerKey);
        }

        $related = new $relatedClass();
        return $related->where($ownerKey, '=', $this->$foreignKey)->first();
    }

    /**
     * Relacionamento 1:N - Esta Model "Tem Várias" Outras.
     * Ex: $categoria->produtos() >> hasMany(Produto::class, 'categoria_id')
     */
    protected function hasMany(string $relatedClass, string $foreignKey, string $localKey = 'id'): mixed
    {
        if ($this->relationDefinitionMode) {
            return new RelationDefinition('hasMany', $relatedClass, $foreignKey, $localKey);
        }

        $related = new $relatedClass();

        // Evita bugar buscando foreign key = null pra objetos recém instanciados sem dados salvos.
        if ($this->$localKey === null) {
            return [];
        }

        return $related->where($foreignKey, '=', $this->$localKey)->get();
    }

    /**
     * Relacionamento 1:1 - Esta Model "Tem Um" Outro.
     * Ex: $usuario->endereco() >> hasOne(Endereco::class, 'usuario_id')
     */
    protected function hasOne(string $relatedClass, string $foreignKey, string $localKey = 'id'): mixed
    {
        if ($this->relationDefinitionMode) {
            return new RelationDefinition('hasOne', $relatedClass, $foreignKey, $localKey);
        }

        $related = new $relatedClass();

        if ($this->$localKey === null) {
            return null;
        }

        return $related->where($foreignKey, '=', $this->$localKey)->first();
    }

    /**
     * Filtra os dados de entrada usando o Mass Assignment (lista $fillable).
     */
    protected function filterFillable(array $data): array
    {
        if (empty($this->fillable)) {
            // Em modo debug, avisa o desenvolvedor que nenhuma coluna será aceita sem $fillable
            if (function_exists('env') && env('APP_DEBUG', false)) {
                trigger_error(
                    static::class . ': Tentativa de Mass Assignment sem $fillable definido. Todos os campos foram bloqueados por segurança.',
                    E_USER_NOTICE
                );
            }
            return [];
        }

        return array_intersect_key($data, array_flip($this->fillable));
    }

    /**
     * Executa operações dentro de uma transação de banco de dados.
     * Commita em sucesso, faz rollback em qualquer exceção.
     *
     * Uso:
     *   $model->transaction(function() use ($pedidoData, $itensPedido) {
     *       $pedidoId = (new Pedido())->insert($pedidoData);
     *       foreach ($itensPedido as $item) {
     *           (new ItemPedido())->insert($item + ['pedido_id' => $pedidoId]);
     *       }
     *       return $pedidoId;
     *   });
     *
     * @param callable $callback
     * @return mixed Valor retornado pelo callback
     */
    public function transaction(callable $callback): mixed
    {
        return \Core\Database\Connection::transaction($callback);
    }

    /**
     * Converte o model para array, respeitando os campos ocultos.
     */
    public function toArray(): array
    {
        $data = get_object_vars($this);
        
        // Remove propriedades internas do framework
        unset($data['db'], $data['table'], $data['primaryKey'], $data['fillable'], $data['hidden'], $data['timestamps'], $data['softDeletes'], $data['loadedRelations'], $data['relationDefinitionMode']);

        // Adiciona as relações carregadas
        foreach ($this->loadedRelations as $key => $value) {
            if ($value instanceof Model) {
                $data[$key] = $value->toArray();
            } elseif (is_array($value)) {
                $data[$key] = array_map(fn($item) => $item instanceof Model ? $item->toArray() : $item, $value);
            } else {
                $data[$key] = $value;
            }
        }

        // Remove campos protegidos (hidden)
        foreach ($this->hidden as $field) {
            unset($data[$field]);
        }

        return $data;
    }

    /**
     * Suporte para json_encode()
     */
    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }

    /**
     * Controla o que aparece no var_dump() e debugadores
     */
    public function __debugInfo(): array
    {
        return $this->toArray();
    }
}
