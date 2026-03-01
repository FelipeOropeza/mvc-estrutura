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

    /**
     * Métodos Mágicos para getters/setters dinâmicos
     * Isso permite chamar $user->nome mesmo que 'nome' não esteja declarado publicamente.
     * 
     * @param string $name
     * @return mixed
     */
    public function __get(string $name): mixed
    {
        return $this->$name ?? null;
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
        $stmt = $this->db->prepare("SELECT * FROM {$this->table}");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_CLASS, static::class);
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
        $data = $this->beforeInsert($data);

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
        $data = $this->beforeUpdate($data);

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

    /**
     * Hook chamado ANTES de criar um registro novo no banco.
     */
    protected function beforeInsert(array $data): array
    {
        return $data;
    }

    /**
     * Hook chamado ANTES de atualizar um registro existente no banco.
     */
    protected function beforeUpdate(array $data): array
    {
        return $data;
    }
}
