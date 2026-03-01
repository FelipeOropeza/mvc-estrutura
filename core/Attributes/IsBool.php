<?php

declare(strict_types=1);

namespace Core\Attributes;

use Attribute;
use Core\Contracts\ValidationRule;

#[Attribute]
class IsBool implements ValidationRule
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

        // Aceita '1', '0', 'true', 'false', boolean nativo do PHP e converte se for
        $filtered = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        if ($filtered === null) {
            return $this->message ?? "O campo {$attribute} precisa ser exclusivamente verdadeiro ou falso.";
        }

        return null;
    }
}
