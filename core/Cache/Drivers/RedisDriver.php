<?php

declare(strict_types=1);

namespace Core\Cache\Drivers;

use Core\Cache\CacheInterface;
use Predis\Client;

class RedisDriver implements CacheInterface
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

    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->redis->get($key);
        return $value !== null ? unserialize($value) : $default;
    }

    public function set(string $key, mixed $value, int $seconds = 3600): bool
    {
        $this->redis->setex($key, $seconds, serialize($value));
        return true;
    }

    public function has(string $key): bool
    {
        return (bool)$this->redis->exists($key);
    }

    public function forget(string $key): bool
    {
        $this->redis->del($key);
        return true;
    }

    public function flush(): bool
    {
        $this->redis->flushdb();
        return true;
    }

    public function remember(string $key, int $seconds, \Closure $callback): mixed
    {
        $value = $this->get($key);
        if ($value !== null) return $value;

        $value = $callback();
        $this->set($key, $value, $seconds);
        return $value;
    }
}
