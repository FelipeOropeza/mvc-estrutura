<?php

declare(strict_types=1);

namespace Core\Attributes;

use Attribute;
use Core\Contracts\ValidationRule;

#[Attribute]
class Email implements ValidationRule
{
    public function validate(string $attribute, mixed $value, array $allData = []): ?string
    {
        if ($value !== null && trim((string)$value) !== '') {
            if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                return "O campo {$attribute} deve ser um e-mail vÃ¡lido.";
            }
        }
        return null;
    }
}
