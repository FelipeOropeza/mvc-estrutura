<?php

declare(strict_types=1);

namespace Core\Database;

use PDO;

abstract class Model
{
    /** @var PDO */
    protected PDO $db;

    /** @var string|null Nome da tabela (se null, será plural do nome da classe) */
    protected ?string $table = null;

    /** @var string Nome da chave primária */
    protected string $primaryKey = 'id';

    /** @var array Lista de colunas seguras e permitidas para serem manipuladas em massa */
    protected array $fillable = [];

    /** @var bool Ativa/Desativa controle automático das colunas created_at e updated_at */
    public bool $timestamps = true;

    public function __construct()
    {
        $this->db = Connection::getInstance();

        if ($this->table === null) {
            $classPath = explode('\\', static::class);
            $className = end($classPath);
            $this->table = strtolower($className) . 's'; // Muito básico, idealmente seria pluralizador
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
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id LIMIT 1");
        $stmt->bindValue(':id', $id);
        $stmt->execute();
        $stmt->setFetchMode(PDO::FETCH_CLASS, static::class);
        $result = $stmt->fetch();

        return $result !== false ? $result : null;
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

        $columns = implode(', ', array_keys($data));
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
            $fields[] = "{$key} = :{$key}";
        }
        $fieldsStr = implode(', ', $fields);

        $sql = "UPDATE {$this->table} SET {$fieldsStr} WHERE {$this->primaryKey} = :id";

        $stmt = $this->db->prepare($sql);

        $stmt->bindValue(':id', $id);
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
        return new QueryBuilder($this->db, $this->table, static::class);
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
     * Filtra os dados de entrada usando o Mass Assignment (lista $fillable).
     */
    protected function filterFillable(array $data): array
    {
        // Se a classe não definiu o $fillable, por retrocompatibilidade a gente aceita tudo. 
        // Idealmente futuramente podemos até lançar Exception se estiver vazio para forçar as boas práticas.
        if (empty($this->fillable)) {
            return $data;
        }

        return array_intersect_key($data, array_flip($this->fillable));
    }
}
