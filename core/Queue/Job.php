<?php

declare(strict_types=1);

namespace Core\Queue;

abstract class Job
{
    /**
     * O número de tentativas caso o job falhe.
     */
    public int $tries = 3;

    /**
     * O tempo de espera entre tentativas (segundos).
     */
    public int $backoff = 0;

    /**
     * Método principal que executa a tarefa.
     */
    abstract public function handle(): void;
}
