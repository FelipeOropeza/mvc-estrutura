<?php

declare(strict_types=1);

namespace Core\Attributes;

use Attribute;
use Core\Contracts\ValidationRule;

#[Attribute]
class Required implements ValidationRule
{
    private ?string $message;

    public function __construct(?string $message = null)
    {
        $this->message = $message;
    }

    public function validate(string $attribute, mixed $value, array $allData = []): ?string
    {
        if ($value === null || trim((string)$value) === '') {
            return $this->message ?? "O campo {$attribute} é obrigatório.";
        }
        return null;
    }
}
