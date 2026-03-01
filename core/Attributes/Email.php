<?php

declare(strict_types=1);

namespace Core\Attributes;

use Attribute;
use Core\Contracts\ValidationRule;

#[Attribute]
class Email implements ValidationRule
{
    private ?string $message;

    public function __construct(?string $message = null)
    {
        $this->message = $message;
    }

    public function validate(string $attribute, mixed $value, array $allData = []): ?string
    {
        if ($value !== null && trim((string)$value) !== '') {
            if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                return $this->message ?? "O campo {$attribute} deve ser um e-mail válido.";
            }
        }
        return null;
    }
}
