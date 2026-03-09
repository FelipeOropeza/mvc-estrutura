<?php

declare(strict_types=1);

namespace Core\Queue;

/**
 * Wrapper para um Job retirado da fila que mantém metadados para controle.
 */
class QueuedJob
{
    public function __construct(
        private object $job,
        private int|string $id,
        private int $attempts,
        private string $queue,
        private QueueInterface $driver
    ) {}

    public function handle(): void
    {
        $this->job->handle();
    }

    public function getJob(): object
    {
        return $this->job;
    }

    public function getId(): int|string
    {
        return $this->id;
    }

    public function getAttempts(): int
    {
        return $this->attempts;
    }

    public function getQueue(): string
    {
        return $this->queue;
    }

    public function delete(): void
    {
        $this->driver->delete($this->queue, $this->id);
    }

    public function release(int $delay = 0): void
    {
        $this->driver->release($this->queue, $this->id, $delay);
    }
}
