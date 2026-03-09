<?php

declare(strict_types=1);

namespace Core\Cache\Drivers;

use Core\Cache\CacheInterface;

class FileDriver implements CacheInterface
{
    private string $path;

    public function __construct()
    {
        $this->path = realpath(__DIR__ . '/../../../storage/cache') ?: __DIR__ . '/../../../storage/cache';
        if (!is_dir($this->path)) {
            mkdir($this->path, 0777, true);
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $file = $this->path . '/' . md5($key);
        if (!file_exists($file)) return $default;

        $content = file_get_contents($file);
        $data = @unserialize($content);

        if (!$data || time() > $data['expires_at']) {
            $this->forget($key);
            return $default;
        }

        return $data['value'];
    }

    public function set(string $key, mixed $value, int $seconds = 3600): bool
    {
        $file = $this->path . '/' . md5($key);
        $data = [
            'value' => $value,
            'expires_at' => time() + $seconds
        ];
        return (bool)file_put_contents($file, serialize($data));
    }

    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    public function forget(string $key): bool
    {
        $file = $this->path . '/' . md5($key);
        if (file_exists($file)) {
            return unlink($file);
        }
        return true;
    }

    public function flush(): bool
    {
        foreach (glob($this->path . '/*') as $file) {
            if (is_file($file)) unlink($file);
        }
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
