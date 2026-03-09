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
     * @return object|null O Job (ou QueuedJob wrapper) ou null se vazio
     */
    public function pop(string $queue = 'default'): ?object;

    /**
     * Remove um job da fila definitivamente.
     */
    public function delete(string $queue, int|string $id): void;

    /**
     * Devolve um job para a fila para ser re-tentado.
     * 
     * @param string $queue Nome da fila
     * @param int|string $id ID do Job
     * @param int $delay Segundos para esperar antes de tornar disponivel novamente
     */
    public function release(string $queue, int|string $id, int $delay = 0): void;
}
