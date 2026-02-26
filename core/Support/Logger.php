<?php

declare(strict_types=1);

namespace Core\Support;

class Logger
{
    protected string $logPath;

    public function __construct(string $filename = 'app.log')
    {
        $this->logPath = __DIR__ . '/../../storage/logs/';

        // Tenta criar o diretório caso ele não exista no clone do projeto
        if (!is_dir($this->logPath)) {
            mkdir($this->logPath, 0777, true);
        }

        $this->logPath .= $filename;
    }

    /**
     * Registra uma mensagem no arquivo de log com o nível especificado.
     */
    public function log(string $level, string $message, array $context = []): void
    {
        $date = date('Y-m-d H:i:s');
        $level = strtoupper($level);

        // Transforma o array de contexto JSON legível
        $contextString = !empty($context) ? ' ' . json_encode($context, JSON_UNESCAPED_UNICODE) : '';

        $formattedMessage = "[{$date}] local.{$level}: {$message}{$contextString}" . PHP_EOL;

        file_put_contents($this->logPath, $formattedMessage, FILE_APPEND);
    }

    public function info(string $message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->log('warning', $message, $context);
    }

    public function debug(string $message, array $context = []): void
    {
        $this->log('debug', $message, $context);
    }
}
