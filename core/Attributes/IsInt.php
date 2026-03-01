<?php

declare(strict_types=1);

namespace Core\Attributes;

use Attribute;
use Core\Contracts\ValidationRule;

#[Attribute]
class IsInt implements ValidationRule
{
    private ?string $message;

    public function __construct(?string $message = null)
    {
        $this->message = $message;
    }

    public function validate(string $attribute, mixed $value, array $allData = []): ?string
    {
        if ($value === null || $value === '') {
            return null; // A obrigatoriedade é papel do #[Required]
        }

        if (filter_var($value, FILTER_VALIDATE_INT) === false) {
            return $this->message ?? "O campo {$attribute} deve ser um número inteiro.";
        }

        return null;
    }
}
