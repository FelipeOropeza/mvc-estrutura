<?php

declare(strict_types=1);

namespace Core\Storage;

class StorageManager
{
    protected static array $disks = [];

    /**
     * Obter a instância do disco solicitado (por padrão, 'local').
     */
    public static function disk(string $name = 'local'): LocalAdapter
    {
        if (isset(self::$disks[$name])) {
            return self::$disks[$name];
        }

        // Simulação básica da leitura do config/filesystems.php 
        // ou hardcoded para o padrão 'local' que escreve em public/storage
        if ($name === 'local') {
            $basePath = realpath(__DIR__ . '/../../public') . '/storage';
            self::$disks[$name] = new LocalAdapter($basePath);
            return self::$disks[$name];
        }

        throw new \Exception("Storage disk [{$name}] is not configured.");
    }
}
