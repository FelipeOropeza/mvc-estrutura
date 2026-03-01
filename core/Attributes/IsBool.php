<?php

declare(strict_types=1);

namespace Core\Attributes;

use Attribute;
use Core\Contracts\ValidationRule;

#[Attribute]
class IsBool implements ValidationRule
{
    public function validate(string $attribute, mixed $value, array $allData = []): ?string
    {
        if ($value === null || $value === '') {
            return null; // A obrigatoriedade é papel do #[Required]
        }

        // Aceita '1', '0', 'true', 'false', boolean type
        $filtered = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        if ($filtered === null) {
            return "O campo {$attribute} deve ser um valor booleano (verdadeiro, falso, 1, 0).";
        }

        return null;
    }
}
