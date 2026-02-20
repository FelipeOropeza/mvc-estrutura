<?php

namespace Core\Contracts;

interface ValidationRule
{
    /**
     * @param string $attribute Nome do campo (ex: 'email')
     * @param mixed $value Valor enviado no form
     * @return string|null Retorna a string de erro se falhar, ou null se for válido
     */
    public function validate(string $attribute, mixed $value): ?string;
}
