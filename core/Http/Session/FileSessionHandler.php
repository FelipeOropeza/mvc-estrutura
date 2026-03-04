<?php

declare(strict_types=1);

namespace Core\Http\Session;

use SessionHandlerInterface;

class FileSessionHandler implements SessionHandlerInterface
{
    private string $path;

    public function __construct(string $path)
    {
        $this->path = rtrim($path, '/\\');

        if (!is_dir($this->path)) {
            mkdir($this->path, 0777, true);
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
        $file = $this->path . DIRECTORY_SEPARATOR . $id;
        if (file_exists($file)) {
            // Utilizamos file_get_contents sem LOCK_SH restritivo no Windows longo, 
            // evitando travar a sessão durante múltiplas requests assíncronas do mesmo usuário.
            // Trade-off: há uma pequena race condition no Worker Mode. Dois workers lendo a mesma sessão 
            // simultaneamente antes de qualquer gravação podem pegar dados desatualizados.
            return (string) file_get_contents($file);
        }
        return '';
    }

    public function write(string $id, string $data): bool
    {
        $file = $this->path . DIRECTORY_SEPARATOR . $id;
        // Salva os dados de modo atômico e leve
        return file_put_contents($file, $data, LOCK_EX) !== false;
    }

    public function destroy(string $id): bool
    {
        $file = $this->path . DIRECTORY_SEPARATOR . $id;
        if (file_exists($file)) {
            unlink($file);
        }
        return true;
    }

    #[\ReturnTypeWillChange]
    public function gc(int $max_lifetime)
    {
        $files = glob($this->path . DIRECTORY_SEPARATOR . '*');
        $deleted = 0;

        if ($files) {
            foreach ($files as $file) {
                if (filemtime($file) + $max_lifetime < time()) {
                    unlink($file);
                    $deleted++;
                }
            }
        }

        return $deleted;
    }
}
