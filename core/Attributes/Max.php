<?php

declare(strict_types=1);

namespace Core\Attributes;

use Attribute;
use Core\Contracts\ValidationRule;

#[Attribute]
class Max implements ValidationRule
{
    private float|int $max;

    public function __construct(float|int $max)
    {
        $this->max = $max;
    }

    public function validate(string $attribute, mixed $value, array $allData = []): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        // Se for Texto (Senha, Nome..)
        if (is_string($value) && !is_numeric($value)) {
            if (mb_strlen($value, 'UTF-8') > $this->max) {
                return "O campo {$attribute} não pode ter mais que {$this->max} caracteres.";
            }
            return null;
        }

        // Se for um Numero
        if (is_numeric($value)) {
            $numValue = $value + 0;
            if ($numValue > $this->max) {
                return "O valor do campo {$attribute} não pode ser maior que {$this->max}.";
            }
            return null;
        }

        // Se for Array
        if (is_array($value)) {
            if (count($value) > $this->max) {
                return "O campo {$attribute} não pode ter mais de {$this->max} itens.";
            }
            return null;
        }

        return "O campo {$attribute} possui um formato inválido para a restrição de máximo.";
    }
}
