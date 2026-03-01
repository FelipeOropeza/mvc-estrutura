<?php

declare(strict_types=1);

namespace Core\Attributes;

use Attribute;
use Core\Contracts\ValidationRule;
use Core\Database\Connection;

#[Attribute]
class ExistsInDatabase implements ValidationRule
{
    private string $table;
    private string $column;

    /**
     * @param string $table A tabela no banco de dados
     * @param string $column A coluna a ser verificada
     */
    public function __construct(string $table, string $column)
    {
        $this->table = $table;
        $this->column = $column;
    }

    public function validate(string $attribute, mixed $value, array $allData = []): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $db = Connection::getInstance();
        $stmt = $db->prepare("SELECT 1 FROM {$this->table} WHERE {$this->column} = :val LIMIT 1");
        $stmt->bindValue(':val', (string) $value);
        $stmt->execute();

        if ($stmt->fetch() === false) {
            return "O registro informado em {$attribute} não existe no banco de dados.";
        }

        return null; // O registro existe
    }
}
