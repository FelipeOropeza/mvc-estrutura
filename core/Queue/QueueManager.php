<?php

declare(strict_types=1);

namespace Core\Queue;

use Core\Queue\Drivers\DatabaseDriver;
use Core\Queue\Drivers\RedisDriver;
use Exception;

class QueueManager
{
    private static ?QueueInterface $driver = null;

    public static function driver(): QueueInterface
    {
        if (self::$driver === null) {
            self::refresh();
        }

        return self::$driver;
    }

    public static function refresh(): void
    {
        $config = env('QUEUE_DRIVER', 'database');

        self::$driver = match ($config) {
            'redis'    => new RedisDriver(),
            'database' => new DatabaseDriver(),
            default    => throw new Exception("Driver de fila [{$config}] não suportado.")
        };
    }

    /**
     * Envia um Job para a fila.
     */
    public static function push(object $job, string $queue = 'default'): bool
    {
        return self::driver()->push($job, $queue);
    }

    /**
     * Retira um Job da fila.
     */
    public static function pop(string $queue = 'default'): ?object
    {
        return self::driver()->pop($queue);
    }
}
