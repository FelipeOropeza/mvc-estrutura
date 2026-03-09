<?php

declare(strict_types=1);

namespace Core\Queue\Drivers;

use Core\Queue\QueueInterface;
use Core\Queue\QueuedJob;
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
        
        $sql = "INSERT INTO {$this->table} (queue, payload, attempts, reserved_at, available_at, created_at) 
                VALUES (:queue, :payload, 0, NULL, :now, :now)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':queue', $queue);
        $stmt->bindValue(':payload', $payload);
        $now = time();
        $stmt->bindValue(':now', $now);

        return $stmt->execute();
    }

    public function pop(string $queue = 'default'): ?object
    {
        $this->db->beginTransaction();
        
        try {
            $now = time();
            $driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
            
            // SQLite não suporta FOR UPDATE, mas como ele trava o arquivo todo em transação, é seguro
            $forUpdate = ($driver === 'mysql') ? 'FOR UPDATE' : '';

            $sql = "SELECT * FROM {$this->table} 
                    WHERE queue = :queue 
                    AND (reserved_at IS NULL OR reserved_at <= :timeout)
                    AND available_at <= :now
                    ORDER BY id ASC LIMIT 1 {$forUpdate}";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':queue', $queue);
            $stmt->bindValue(':now', $now);
            $stmt->bindValue(':timeout', $now - 60); // Timeout de 60 segundos
            $stmt->execute();
            
            $jobData = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$jobData) {
                $this->db->rollBack();
                return null;
            }

            // Reserva o job incrementando tentativas
            $updateStmt = $this->db->prepare("UPDATE {$this->table} SET reserved_at = :now, attempts = attempts + 1 WHERE id = :id");
            $updateStmt->execute(['now' => $now, 'id' => $jobData['id']]);

            $this->db->commit();
            
            $job = unserialize($jobData['payload']);
            
            return new QueuedJob(
                $job, 
                (int)$jobData['id'], 
                (int)$jobData['attempts'] + 1, 
                $queue,
                $this
            );
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            logger()->error("Erro ao dar pop no Database Queue: " . $e->getMessage());
            return null;
        }
    }

    public function delete(string $queue, int|string $id): void
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = :id");
        $stmt->execute(['id' => $id]);
    }

    public function release(string $queue, int|string $id, int $delay = 0): void
    {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET reserved_at = NULL, available_at = :available WHERE id = :id");
        $stmt->execute([
            'available' => time() + $delay,
            'id' => $id
        ]);
    }
}
