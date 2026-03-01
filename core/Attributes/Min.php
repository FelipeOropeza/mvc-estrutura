<?php

declare(strict_types=1);

namespace Core\Attributes;

use Attribute;
use Core\Contracts\ValidationRule;

#[Attribute]
class Min implements ValidationRule
{
    private float|int $min;

    public function __construct(float|int $min)
    {
        $this->min = $min;
    }

    public function validate(string $attribute, mixed $value, array $allData = []): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        // Se for um Input de Senha ou Texto normal
        if (is_string($value) && !is_numeric($value)) {
            if (mb_strlen($value, 'UTF-8') < $this->min) {
                return "O campo {$attribute} precisa ter no mínimo {$this->min} caracteres.";
            }
            return null;
        }

        // Se for um Numero Inteiro ou Float
        if (is_numeric($value)) {
            $numValue = $value + 0; // cast magico para int ou float
            if ($numValue < $this->min) {
                return "O valor do campo {$attribute} não pode ser menor que {$this->min}.";
            }
            return null;
        }

        // Se for Array ou Arquivo
        if (is_array($value)) {
            if (count($value) < $this->min) {
                return "O campo {$attribute} precisa ter pelo menos {$this->min} itens.";
            }
            return null;
        }

        return "O campo {$attribute} possui um formato inválido para a restrição de mínimo.";
    }
}
