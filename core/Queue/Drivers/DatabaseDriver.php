<?php

declare(strict_types=1);

namespace Core\Queue\Drivers;

use Core\Queue\QueueInterface;
use Core\Database\Connection;
use PDO;

class DatabaseDriver implements QueueInterface
{
    private PDO $db;
    private string $table = 'jobs';

    public function __construct()
    {
        $this->db = Connection::getInstance();
    }

    public function push(object $job, string $queue = 'default'): bool
    {
        $payload = serialize($job);
        
        $sql = "INSERT INTO {$this->table} (queue, payload, attempts, available_at, created_at) 
                VALUES (:queue, :payload, 0, :now, :now)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':queue', $queue);
        $stmt->bindValue(':payload', $payload);
        $now = time();
        $stmt->bindValue(':now', $now);

        return $stmt->execute();
    }

    public function pop(string $queue = 'default'): ?object
    {
        // Lógica simplificada de reserva e deleção imediata
        $this->db->beginTransaction();
        
        try {
            $sql = "SELECT * FROM {$this->table} 
                    WHERE queue = :queue 
                    ORDER BY id ASC LIMIT 1 FOR UPDATE";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':queue', $queue);
            $stmt->execute();
            
            $jobData = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$jobData) {
                $this->db->rollBack();
                return null;
            }

            // Remove o job da fila para não ser pego por outro worker
            $deleteStmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = :id");
            $deleteStmt->execute(['id' => $jobData['id']]);

            $this->db->commit();
            
            return unserialize($jobData['payload']);
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            logger()->error("Erro ao dar pop no Database Queue: " . $e->getMessage());
            return null;
        }
    }
}
