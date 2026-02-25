<?php

namespace Core\Database;

use PDO;
use PDOException;

abstract class Model
{
    /** @var PDO */
    protected $db;

    /** @var string Nome da tabela (se null, será plural do nome da classe) */
    protected $table = null;

    /** @var string Nome da chave primária */
    protected $primaryKey = 'id';

    public function __construct()
    {
        $this->db = Connection::getInstance();

        if ($this->table === null) {
            $classPath = explode('\\', get_class($this));
            $className = end($classPath);
            $this->table = strtolower($className) . 's'; // Muito básico, idealmente seria pluralizador
        }
    }

    /**
     * Métodos Mágicos para getters/setters dinâmicos
     * Isso permite chamar $user->nome mesmo que 'nome' não esteja declarado publicamente.
     */
    public function __get(string $name)
    {
        return $this->$name ?? null;
    }

    public function __set(string $name, $value)
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
     */
    public function all(): array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table}");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_CLASS, static::class);
    }

    /**
     * Busca um registro pelo seu ID
     */
    public function find($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id LIMIT 1");
        $stmt->bindValue(':id', $id);
        $stmt->execute();
        $stmt->setFetchMode(PDO::FETCH_CLASS, static::class);
        return $stmt->fetch() ?: null;
    }

    /**
     * Insere um novo registro no banco de dados
     * 
     * @param array $data Ex: ['nome' => 'Felipe', 'email' => 'felipe@etc.com']
     * @return int O ID inserido
     */
    public function insert(array $data): int
    {
        $columns = implode(', ', array_keys($data));
        // Cria os placeholders (:nome, :email)
        $placeholders = ':' . implode(', :', array_keys($data));

        $sql = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";

        $stmt = $this->db->prepare($sql);

        foreach ($data as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }

        $stmt->execute();

        return (int)$this->db->lastInsertId();
    }

    /**
     * Atualiza um registro existente
     * 
     * @param int $id
     * @param array $data Ex: ['nome' => 'Felipe 2']
     * @return bool
     */
    public function update($id, array $data): bool
    {
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
     * @param int|array $id
     * @return bool
     */
    public function delete($id): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id);
        return $stmt->execute();
    }

    /**
     * Retorna a query builder caso queira fazer queries customizadas no controller
     */
    public function query(string $sql, array $params = [])
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
