<?php

declare(strict_types=1);

namespace Core\Attributes;

use Attribute;
use Core\Contracts\ValidationRule;
use Core\Database\Connection;

#[Attribute]
class Unique implements ValidationRule
{
    /**
     * @param string $table O nome da tabela para verificar
     * @param string $column O nome da coluna (ex: email)
     * @param string|null $ignore O nome do campo do DTO que contém o ID a ser ignorado (ex: 'id')
     * @param string|null $message Mensagem customizada de erro
     */
    public function __construct(
        private string $table,
        private string $column,
        private ?string $ignore = null,
        private ?string $message = null
    ) {}

    public function validate(string $attribute, mixed $value, array $allData = []): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $db = Connection::getInstance();
        
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE {$this->column} = :value";
        $params = ['value' => $value];

        // Se passarmos um campo pra ignorar (ex: 'id' em um Update), ele busca no array de dados
        if ($this->ignore && isset($allData[$this->ignore])) {
            // Em cenários de edição, o ignore costuma ser a chave primária 'id'
            $sql .= " AND {$this->ignore} != :ignore";
            $params['ignore'] = $allData[$this->ignore];
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
