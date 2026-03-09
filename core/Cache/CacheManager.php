<?php

declare(strict_types=1);

namespace Core\Cache;

use Core\Cache\Drivers\FileDriver;
use Core\Cache\Drivers\RedisDriver;
use Exception;

class CacheManager
{
    private static ?CacheInterface $driver = null;

    public static function driver(): CacheInterface
    {
        if (self::$driver !== null) {
            // Auto-recovery: Tenta um "has" básico para ver se a conexão (Redis, etc) ainda está viva
            // Essencial para Worker Mode (FrankenPHP)
            try {
                self::$driver->has('_ping');
            } catch (\Throwable) {
                self::$driver = null; // Caiu? Força reconexão
            }
        }

        if (self::$driver === null) {
            self::refresh();
        }

        return self::$driver;
    }

    public static function refresh(): void
    {
        $config = env('CACHE_DRIVER', 'file');

        self::$driver = match ($config) {
            'redis' => new RedisDriver(),
            'file'  => new FileDriver(),
            default => throw new Exception("Driver de cache [{$config}] não suportado.")
        };
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return self::driver()->get($key, $default);
    }

    public static function set(string $key, mixed $value, int $seconds = 3600): bool
    {
        return self::driver()->set($key, $value, $seconds);
    }

    public static function remember(string $key, int $seconds, \Closure $callback): mixed
    {
        return self::driver()->remember($key, $seconds, $callback);
    }
}
