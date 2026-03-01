<?php

declare(strict_types=1);

namespace Core\Attributes;

use Attribute;
use Core\Contracts\ValidationRule;

#[Attribute]
class Required implements ValidationRule
{
    public function validate(string $attribute, mixed $value, array $allData = []): ?string
    {
        if ($value === null || trim((string)$value) === '') {
            return "O campo {$attribute} Ã© obrigatÃ³rio.";
        }
        return null;
    }
}
