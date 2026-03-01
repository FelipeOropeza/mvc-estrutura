<?php

declare(strict_types=1);

namespace App\Mutators;

use Attribute;
use Core\Contracts\Mutator;

#[Attribute]
class LimpaCpf implements Mutator
{
    /**
     * Altera um valor de entrada antes dele ser encaminhado para o Banco de Dados.
     */
    public function mutate(string $attribute, mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return $value;
        }

        // TODO: Implemente a lógica de conversão ou higienização
        // if (is_string($value)) {
        //      return preg_replace('/[^0-9]/', '', $value); // Limpa tudo que não é número
        // }

        return $value;
    }
}
