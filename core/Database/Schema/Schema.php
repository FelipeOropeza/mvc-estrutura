<?php

declare(strict_types=1);

namespace Core\Database\Schema;

use Core\Database\Connection;

class Schema
{
    /**
     * Create a new table on the schema.
     */
    public static function create(string $table, callable $callback): void
    {
        $blueprint = new Blueprint($table);

        // O Dev define as colunas da tabela na Callbback function dele
        $callback($blueprint);

        // Traduz as ordens do blueprint para mysql bruto
        $sql = $blueprint->toSql();
        // Altera silenciosamente para não explodir em refazer a mesma tabela durante desenvolvimento bruto atual
        $sql = str_replace("CREATE TABLE `{$table}`", "CREATE TABLE IF NOT EXISTS `{$table}`", $sql);

        // Envia direto para a PDO!
        Connection::getInstance()->exec($sql);

        // Vamos logar para checarmos o SQL gerado:
        echo "Gerando Tabela: {$table} \n";
        // log silencioso
    }

    /**
     * Drop a table from the schema.
     */
    public static function dropIfExists(string $table): void
    {
        $sql = "DROP TABLE IF EXISTS `{$table}`;";

        // Executa limpeza:
        Connection::getInstance()->exec($sql);

        echo "Excluindo: {$table} \n";
    }
}
