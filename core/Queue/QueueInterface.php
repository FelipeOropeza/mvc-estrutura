<?php

declare(strict_types=1);

namespace Core\Queue;

interface QueueInterface
{
    /**
     * Adiciona um Job à fila.
     * 
     * @param object $job Instância da classe Job
     * @param string $queue Nome da fila (opcional)
     */
    public function push(object $job, string $queue = 'default'): bool;

    /**
     * Retira e retorna o próximo Job da fila.
     * 
     * @param string $queue Nome da fila
     * @return object|null O Job ou null se vazio
     */
    public function pop(string $queue = 'default'): ?object;
}
