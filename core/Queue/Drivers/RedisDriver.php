<?php

declare(strict_types=1);

namespace Core\Queue\Drivers;

use Core\Queue\QueueInterface;
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

        return unserialize($payload);
    }
}
