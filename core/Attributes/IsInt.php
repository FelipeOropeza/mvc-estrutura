<?php

declare(strict_types=1);

namespace Core\Attributes;

use Attribute;
use Core\Contracts\ValidationRule;

#[Attribute]
class IsInt implements ValidationRule
{
    private ?int $min;
    private ?int $max;

    public function __construct(int $min = null, int $max = null)
    {
        $this->min = $min;
        $this->max = $max;
    }

    public function validate(string $attribute, mixed $value, array $allData = []): ?string
    {
        if ($value === null || $value === '') {
            return null; // A obrigatoriedade é papel do #[Required]
        }

        if (filter_var($value, FILTER_VALIDATE_INT) === false) {
            return "O campo {$attribute} deve ser um número inteiro válido.";
        }

        $intValue = (int) $value;

        if ($this->min !== null && $intValue < $this->min) {
            return "O campo {$attribute} não pode ser menor que {$this->min}.";
        }

        if ($this->max !== null && $intValue > $this->max) {
            return "O campo {$attribute} não pode ser maior que {$this->max}.";
        }

        return null;
    }
}
