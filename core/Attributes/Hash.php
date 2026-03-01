<?php

declare(strict_types=1);

namespace Core\Attributes;

use Attribute;
use Core\Contracts\Mutator;

#[Attribute]
class Hash implements Mutator
{
    /**
     * Aplica o algoritmo de criptografia padrão do PHP (Bcrypt/Argon2)
     * e garante que não re-criptografe um hash já pronto no Update.
     */
    public function mutate(string $attribute, mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return $value;
        }

        if (is_string($value)) {
            $info = password_get_info($value);
            // 'unknown' significa que é uma string pura e não um hash validado
            if ($info['algoName'] === 'unknown') {
                return password_hash($value, PASSWORD_DEFAULT);
            }
        }

        return $value;
    }
}
