<?php

declare(strict_types=1);

namespace Core\Attributes;

use Attribute;
use Core\Contracts\ValidationRule;
use Core\Database\Connection;

#[Attribute]
class Unique implements ValidationRule
{
    public function __construct(
        private string $table,
        private string $column,
        private ?string $ignore = null,
        private ?string $message = null,
        private array $where = []
    ) {}

    public function validate(string $attribute, mixed $value, array $allData = []): ?string
    {
        if ($value === null || $value === "") {
            return null;
        }

        $db = Connection::getInstance();

        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE `{$this->column}` = :value";
        $params = ["value" => $value];

        // Se passarmos um campo pra ignorar (ex: 'id' em um Update), ele busca no array de dados
        if ($this->ignore && isset($allData[$this->ignore])) {
            $sql .= " AND `{$this->ignore}` != :ignore";
            $params["ignore"] = $allData[$this->ignore];
        }

        // Condições extras (ex: onde usuario_id = x)
        foreach ($this->where as $dbColumn => $dtoField) {
            // Se o valor existe no DTO, usamos ele. Caso contrário, usamos o valor literal do array
            $whereValue = $allData[$dtoField] ?? $dtoField;
            $paramName = "where_" . str_replace(".", "_", $dbColumn);

            $sql .= " AND `{$dbColumn}` = :{$paramName}";
            $params[$paramName] = $whereValue;
        }

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $count = (int) $stmt->fetchColumn();

        if ($count > 0) {
            return $this->message ?? "O valor preenchido em {$attribute} já está em uso.";
        }

        return null;
    }
}
