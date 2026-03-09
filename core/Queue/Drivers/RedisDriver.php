<?php

declare(strict_types=1);

namespace Core\Queue\Drivers;

use Core\Queue\QueueInterface;
use Core\Queue\QueuedJob;
use Predis\Client;

class RedisDriver implements QueueInterface
{
    private Client $redis;

    public function __construct()
    {
        $this->redis = new Client([
            'scheme' => 'tcp',
            'host'   => env('REDIS_HOST', '127.0.0.1'),
            'port'   => (int)env('REDIS_PORT', 6379),
            'password' => env('REDIS_PASSWORD'),
        ]);
    }

    public function push(object $job, string $queue = 'default'): bool
    {
        $payload = serialize($job);
        $this->redis->rpush("queues:{$queue}", [$payload]);
        return true;
    }

    public function pop(string $queue = 'default'): ?object
    {
        // lpop retira o item da esquerda da lista (fila)
        $payload = $this->redis->lpop("queues:{$queue}");
        
        if (!$payload) {
            return null;
        }

        $job = unserialize($payload);
        
        // No Redis simplificado, usamos o payload como ID para de-serializar se precisar re-inserir.
        // O QueuedJob agora guarda o nome da fila correta para o release() não bugar.
        return new QueuedJob($job, $payload, 0, $queue, $this);
    }

    public function delete(string $queue, int|string $id): void
    {
        // No lpop o item já sai da fila, nada a fazer aqui na versão simples
    }

    public function release(string $queue, int|string $id, int $delay = 0): void
    {
        // Re-insere no final da fila correta (rpush) se falhar.
        // O $delay é ignorado no Redis simples (precisaria de sub-listas de wait/delayed)
        $this->redis->rpush("queues:{$queue}", [(string)$id]);
    }
}
