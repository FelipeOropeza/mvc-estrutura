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

    /** @var array Lista de métodos ou propriedades calculadas para incluir na serialização (ex: 'melhor_nota') */
    protected array $appends = [];

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

    /** 
     * @var array Cache de resultados do atributo #[Broadcast].
     * Estático e compartilhado entre todas as subclasses para máxima performance.
     * Em modo Worker, este cache persiste entre requisições. Se houver mudanças nos attributes em runtime (edge case),
     * a reciclagem do worker ou restart resolvem a invalidação.
     */
    protected static array $broadcastCache = [];

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
     * Facilita chamadas estáticas (Ex: User::find(1) em vez de (new User)->find(1))
     */
    public static function __callStatic(string $name, array $arguments): mixed
    {
        return (new static())->$name(...$arguments);
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
     * Salva o estado atual do objeto no banco de dados.
     * Decide automaticamente entre INSERT ou UPDATE.
     * 
     * @return bool
     */
    public function save(): bool
    {
        $pk = $this->primaryKey;
        $data = [];

        // Coleta todas as propriedades dynamic/public do objeto para o array de dados
        $raw = (array) $this;
        foreach ($raw as $key => $value) {
            $cleanKey = ltrim($key, "\0");
            $cleanKey = preg_replace('/^[^\0]+\0/', '', $cleanKey) ?: $cleanKey;

            // Pula propriedades do framework
            if (in_array($cleanKey, ['db', 'table', 'primaryKey', 'fillable', 'hidden', 'timestamps', 'softDeletes', 'loadedRelations', 'relationDefinitionMode'], true)) {
                continue;
            }
            $data[$cleanKey] = $value;
        }

        if (isset($this->$pk) && $this->$pk) {
            return $this->update($this->$pk, $data);
        }

        $id = $this->insert($data);
        if ($id > 0) {
            $this->$pk = $id;
            return true;
        }

        return false;
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
            // Conversão explícita para evitar que boolean false vire string vazia ''
            $val = is_bool($value) ? (int) $value : $value;
            $stmt->bindValue(':' . $key, $val);
        }

        $stmt->execute();
        $id = (int) $this->db->lastInsertId();

        if ($id > 0) {
            $this->{$this->primaryKey} = $id;
            $this->checkAndBroadcast('inserted');
        }

        return $id;
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
            // Conversão explícita para evitar que boolean false vire string vazia ''
            $val = is_bool($value) ? (int) $value : $value;
            $stmt->bindValue(':' . $key, $val);
        }

        $success = $stmt->execute();

        if ($success) {
            $this->checkAndBroadcast('updated');
        }

        return $success;
    }

    /**
     * Deleta um registro pelo ID
     * 
     * @param mixed $id
     * @return bool
     */
    public function delete(mixed $id = null): bool
    {
        $id = $id ?? ($this->{$this->primaryKey} ?? null);

        if (!$id) {
            return false;
        }

        if (property_exists($this, 'softDeletes') && $this->softDeletes) {
            $sql = "UPDATE {$this->table} SET deleted_at = :deleted_at WHERE {$this->primaryKey} = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':id', $id);
            $stmt->bindValue(':deleted_at', date('Y-m-d H:i:s'));
            
            $success = $stmt->execute();
            if ($success) {
                $this->checkAndBroadcast('deleted');
            }
            
            return $success;
        }

        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id);

        $success = $stmt->execute();
        if ($success) {
            $this->checkAndBroadcast('deleted');
        }

        return $success;
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
     * Define as colunas que devem ser selecionadas.
     * Ex: $produto->select('id, nome, preco')->get();
     */
    public function select(string $columns): QueryBuilder
    {
        return $this->newQuery()->select($columns);
    }

    /**
     * Inicia uma verificação fluente na Tabela
     * Ex: $produto->where('preco', '>', 50)->get();
     */
    public function where(string|\Closure $column, ?string $operator = null, mixed $value = null): QueryBuilder
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
     */
    public function join(string $table, string $condition, string $type = 'INNER'): QueryBuilder
    {
        return $this->newQuery()->join($table, $condition, $type);
    }

    /**
     * Inicia um LEFT JOIN fluente.
     */
    public function leftJoin(string $table, string $condition): QueryBuilder
    {
        return $this->newQuery()->leftJoin($table, $condition);
    }

    /**
     * Adiciona uma verificação IS NULL.
     */
    public function whereNull(string $column): QueryBuilder
    {
        return $this->newQuery()->whereNull($column);
    }

    /**
     * Adiciona uma verificação IS NOT NULL.
     */
    public function whereNotNull(string $column): QueryBuilder
    {
        return $this->newQuery()->whereNotNull($column);
    }

    /**
     * Inicia uma verificação fluente OR na Tabela
     */
    public function orWhere(string|\Closure $column, ?string $operator = null, mixed $value = null): QueryBuilder
    {
        return $this->newQuery()->orWhere($column, $operator, $value);
    }

    /**
     * Inicia uma verificação fluente IN na Tabela
     */
    public function whereIn(string $column, array|\Closure $values): QueryBuilder
    {
        return $this->newQuery()->whereIn($column, $values);
    }

    /**
     * Inicia uma verificação fluente OR IN na Tabela
     */
    public function orWhereIn(string $column, array $values): QueryBuilder
    {
        return $this->newQuery()->orWhereIn($column, $values);
    }

    /**
     * Inicia o Eager Loading de relações.
     */
    public function with(string|array $relations, string ...$extra): QueryBuilder
    {
        return $this->newQuery()->with($relations, ...$extra);
    }

    /**
     * Carrega relações para a instância atual do Model (Lazy Loading manual otimizado).
     *
     * @param string|array $relations
     * @return $this
     */
    public function load(string|array $relations): self
    {
        $this->newQuery()->with($relations)->loadForModels([$this]);
        return $this;
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
        // Cast captura tanto propriedades declaradas quanto dinâmicas (setadas pelo PDO FETCH_CLASS).
        // Propriedades private/protected ficam com chaves mangled pelo PHP, por isso filtramos.
        $raw = (array) $this;

        $data = [];
        foreach ($raw as $key => $value) {
            // Descarta chaves mangled de private/protected (ex: "\0ClassName\0prop")
            $cleanKey = ltrim($key, "\0");
            $cleanKey = preg_replace('/^[^\0]+\0/', '', $cleanKey) ?: $cleanKey;

            // Remove propriedades internas do framework
            if (in_array($cleanKey, ['db', 'table', 'primaryKey', 'fillable', 'hidden', 'timestamps', 'softDeletes', 'loadedRelations', 'relationDefinitionMode'], true)) {
                continue;
            }

            $data[$cleanKey] = $value;
        }

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

        // Adiciona campos do $appends chamando os métodos dinamicamente
        foreach ($this->appends as $method) {
            if (method_exists($this, $method)) {
                $result = $this->$method();
                $data[$method] = ($result instanceof Model) ? $result->toArray() : $result;
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

    /**
     * Verifica se o Model tem o atributo #[Broadcast] e dispara o evento via Mercure.
     * 
     * @param string $action 'inserted', 'updated' ou 'deleted'
     */
    protected function checkAndBroadcast(string $action): void
    {
        $class = static::class;

        // Se já sabemos que não tem, retorna rápido (Performance)
        if (isset(self::$broadcastCache[$class]) && self::$broadcastCache[$class] === false) {
            return;
        }

        if (!isset(self::$broadcastCache[$class])) {
            $reflection = new \ReflectionClass($class);
            $attributes = $reflection->getAttributes(\Core\Attributes\Broadcast::class);

            if (empty($attributes)) {
                self::$broadcastCache[$class] = false;
                return;
            }

            self::$broadcastCache[$class] = $attributes[0]->newInstance();
        }

        /** @var \Core\Attributes\Broadcast $broadcast */
        $broadcast = self::$broadcastCache[$class];

        // Se o modo estiver setado e não condiz com a ação, para aqui.
        if ($broadcast->mode === 'create' && $action !== 'inserted') return;
        if ($broadcast->mode === 'update' && $action !== 'updated') return;

        // Se houver relações para carregar antes do broadcast
        if (!empty($broadcast->with)) {
            $this->load($broadcast->with);
        }

        $topic = $broadcast->topic ?? $this->table;

        broadcast((string) $topic, [
            'event' => $broadcast->event,
            'action' => $action,
            'model' => $class,
            'id' => $this->{$this->primaryKey} ?? null,
            'data' => $this->toArray()
        ]);
    }
}
