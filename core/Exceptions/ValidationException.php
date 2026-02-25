<?php

namespace Core\Exceptions;

use Exception;

class ValidationException extends Exception
{
    public array $errors;
    public array $oldInput;

    public function __construct(array $errors, array $oldInput = [])
    {
        parent::__construct("Erro de Validação Atributiva", 422);
        $this->errors = $errors;
        $this->oldInput = $oldInput;
    }
}
