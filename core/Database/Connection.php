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

            $driver = getenv('DB_CONNECTION') ?: $configMaster['default'];
            $dbConfig = $configMaster['connections'][$driver] ?? null;

            if (!$dbConfig) {
                throw new Exception("Driver de banco de dados '{$driver}' não configurado.");
            }

            try {
                if ($driver === 'mysql') {
                    $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['database']};charset={$dbConfig['charset']}";
                    self::$instance = new PDO($dsn, $dbConfig['username'], $dbConfig['password']);
                } elseif ($driver === 'pgsql') {
                    $dsn = "pgsql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['database']}";
                    self::$instance = new PDO($dsn, $dbConfig['username'], $dbConfig['password']);
                } elseif ($driver === 'sqlite') {
                    $path = $dbConfig['database'];
                    // Suporte a caminho relativo (a partir da raiz do projeto)
                    if ($path !== ':memory:' && !str_starts_with($path, '/') && !str_contains($path, ':')) {
                        $path = rtrim(dirname(__DIR__, 2), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $path;
                    }
                    self::$instance = new PDO("sqlite:{$path}");
                } else {
                    throw new Exception("Driver de banco de dados '{$driver}' não suportado. Drivers disponíveis: mysql, pgsql, sqlite.");
                }

                // Configura o PDO para lançar exceções e trazer dados como array associativo por padrão
                self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                self::$instance->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                self::$instance->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            } catch (PDOException $e) {
                // Lança a exceção para o Handler tratar corretamente (sem echo/exit)
                // Isso evita vazar detalhes internos em produção
                throw new \RuntimeException('Falha na conexão com o banco de dados.', 500, $e);
            }
        }

        return self::$instance;
    }

    /**
     * Executa um callback dentro de uma transação de banco de dados.
     * Faz commit automático em sucesso e rollback em qualquer exceção.
     *
     * Uso:
     *   Connection::transaction(function(PDO $db) {
     *       // insert pedido
     *       // debitar estoque
     *   });
     *
     * @param callable $callback Recebe a instância PDO como argumento
     * @return mixed O valor retornado pelo callback
     * @throws \Throwable Se o callback lançar qualquer exceção, o rollback é feito e ela relanceada
     */
    public static function transaction(callable $callback): mixed
    {
        $db = self::getInstance();
        $db->beginTransaction();

        try {
            $result = $callback($db);
            $db->commit();
            return $result;
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }
}
