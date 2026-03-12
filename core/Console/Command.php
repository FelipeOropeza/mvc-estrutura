<?php

declare(strict_types=1);

namespace Core\Console;

abstract class Command
{
    /**
     * A assinatura do comando. Ex: 'promocoes:watch'
     */
    protected string $signature;

    /**
     * A descrição do que o comando faz.
     */
    protected string $description;

    /**
     * Retorna a assinatura do comando.
     */
    public function getSignature(): string
    {
        return $this->signature;
    }

    /**
     * Retorna a descrição do comando.
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Executa o comando.
     */
    abstract public function handle(array $args): void;

    /**
     * Helper para escrever no console com quebra de linha.
     */
    protected function info(string $message): void
    {
        echo "[\033[32mINFO\033[0m] $message\n";
    }

    protected function error(string $message): void
    {
        echo "[\033[31mERRO\033[0m] $message\n";
    }

    protected function line(string $message): void
    {
        echo "$message\n";
    }
}
