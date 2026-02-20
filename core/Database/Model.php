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
            // Se a tabela não for definida, assume o nome da classe em minúsculo (com "s" no final)
            // Ex: "App\Models\Usuario" -> "usuarios"
            $classPath = explode('\\', get_class($this));
            $className = end($classPath);
            $this->table = strtolower($className) . 's'; // Muito básico, idealmente seria um pluralizador.
        }
    }

    /**
     * Busca todos os registros da tabela
     */
    public function all(): array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table}");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Busca um registro pelo seu ID
     */
    public function find($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id LIMIT 1");
        $stmt->bindValue(':id', $id);
        $stmt->execute();
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
