<?php

declare(strict_types=1);

namespace Core\Exceptions;

use RuntimeException;

/**
 * Representa um erro HTTP explícito (4xx, 5xx).
 * Lançado pelo helper abort() e tratado pelo Handler para retornar a response correta.
 */
class HttpException extends RuntimeException
{
    public function __construct(string $message = '', int $statusCode = 500, ?\Throwable $previous = null)
    {
        parent::__construct($message, $statusCode, $previous);
    }
}
