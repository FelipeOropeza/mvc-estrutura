<?php

declare(strict_types=1);

namespace Core\Attributes;

use Attribute;
use Core\Contracts\ValidationRule;

#[Attribute]
class IsFloat implements ValidationRule
{
    private ?float $min;
    private ?float $max;

    public function __construct(float $min = null, float $max = null)
    {
        $this->min = $min;
        $this->max = $max;
    }

    public function validate(string $attribute, mixed $value, array $allData = []): ?string
    {
        if ($value === null || $value === '') {
            return null; // A obrigatoriedade é papel do #[Required]
        }

        if (filter_var($value, FILTER_VALIDATE_FLOAT) === false) {
            return "O campo {$attribute} deve ser um número decimal (float/decimal) válido.";
        }

        $floatValue = (float) $value;

        if ($this->min !== null && $floatValue < $this->min) {
            return "O campo {$attribute} não pode ser menor que {$this->min}.";
        }

        if ($this->max !== null && $floatValue > $this->max) {
            return "O campo {$attribute} não pode ser maior que {$this->max}.";
        }

        return null;
    }
}
