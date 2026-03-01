<?php

declare(strict_types=1);

namespace Core\Contracts;

interface ValidationRule
{
    /**
     * @param string $attribute Nome do campo (ex: 'email')
     * @param mixed $value Valor enviado no form
     * @param array $allData Todos os dados enviados na requisição (útil para checar outros campos)
     * @return string|null Retorna a string de erro se falhar, ou null se for válido
     */
    public function validate(string $attribute, mixed $value, array $allData = []): ?string;
}
