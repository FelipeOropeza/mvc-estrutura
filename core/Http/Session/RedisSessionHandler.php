<?php

declare(strict_types=1);

namespace Core\Http\Session;

use SessionHandlerInterface;
use Redis;

class RedisSessionHandler implements SessionHandlerInterface
{
    private $redis;
    private string $prefix;

    public function __construct(string $host, int $port, string $password = '', string $prefix = 'phpsess:')
    {
        $this->prefix = $prefix;

        if (extension_loaded('redis')) {
            $this->redis = new Redis();
            $this->redis->connect($host, $port);
            if (!empty($password)) {
                $this->redis->auth($password);
            }
        } elseif (class_exists(\Predis\Client::class)) {
            $params = [
                'scheme' => 'tcp',
                'host'   => $host,
                'port'   => $port,
            ];
            if (!empty($password)) {
                $params['password'] = $password;
            }
            $this->redis = new \Predis\Client($params);
        } else {
            throw new \RuntimeException("Extensão 'redis' ou pacote 'predis/predis' é necessário para sessões Redis.");
        }
    }

    public function open(string $path, string $name): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    #[\ReturnTypeWillChange]
    public function read(string $id)
    {
        $data = $this->redis->get($this->prefix . $id);
        return $data !== false ? $data : '';
    }

    public function write(string $id, string $data): bool
    {
        // 120 minutos de sessão default (7200 segundos)
        $lifetime = (int) ini_get('session.gc_maxlifetime') ?: 7200;
        return $this->redis->setex($this->prefix . $id, $lifetime, $data) !== false;
    }

    public function destroy(string $id): bool
    {
        return $this->redis->del($this->prefix . $id) > 0;
    }

    #[\ReturnTypeWillChange]
    public function gc(int $max_lifetime)
    {
        // O Redis cuida automaticamente da remoção das chaves através do setex / TTL
        return 0;
    }
}
