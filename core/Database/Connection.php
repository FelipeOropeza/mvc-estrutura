<?php

declare(strict_types=1);

namespace Core\Database;

use PDO;
use PDOException;
use Exception;

class Connection
{
    private static ?PDO $instance = null;

    private function __construct() {}

    private function __clone() {}

    public static function getInstance(): PDO
    {
        // Ping de saúde: Se a conexão já existe, testa se ela ainda está viva!
        // Essencial para Workers longa duração tipo FrankenPHP
        if (self::$instance !== null) {
            try {
                self::$instance->query('SELECT 1');
            } catch (PDOException) {
                self::$instance = null; // Caiu? Reseta pra reconectar
            }
        }

        if (self::$instance === null) {
            // Usa o Container se disponível, impedindo leitura de File System (I/O) a toda nova conexão
            $container = class_exists(\Core\Support\Container::class) ? \Core\Support\Container::getInstance() : null;
            $configMaster = ($container && $container->has('config')) ? $container->get('config')['database'] : require __DIR__ . '/../../config/database.php';

            // Fallback prático usando variável de ambiente (se a biblioteca dotenv for instalada depois)
            // ou usando o driver padrão definido na configuração.
            $driver = getenv('DB_CONNECTION') ?: $configMaster['default'];
            $dbConfig = $configMaster['connections'][$driver] ?? null;

            if (!$dbConfig) {
                throw new Exception("Driver de banco de dados '{$driver}' não configurado.");
            }

            try {
                if ($driver === 'mysql' || $driver === 'pgsql') {
                    $dsn = "{$driver}:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['database']}";
                    if ($driver === 'mysql') {
                        $dsn .= ";charset={$dbConfig['charset']}";
                    }

                    self::$instance = new PDO($dsn, $dbConfig['username'], $dbConfig['password']);
                } else {
                    throw new Exception("Driver de banco de dados '{$driver}' não suportado.");
                }

                // Configura o PDO para lançar exceções e trazer dados como array associativo por padrão
                self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                self::$instance->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                self::$instance->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            } catch (PDOException $e) {
                http_response_code(500);
                // Em produção, você não deveria exibir o erro real na tela por segurança.
                echo "Erro de conexão com o Banco de Dados: " . $e->getMessage();
                exit;
            }
        }

        return self::$instance;
    }
}
