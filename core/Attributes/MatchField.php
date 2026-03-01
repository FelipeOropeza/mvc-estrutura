<?php

declare(strict_types=1);

namespace Core\Attributes;

use Attribute;
use Core\Contracts\ValidationRule;

#[Attribute]
class MatchField implements ValidationRule
{
    private string $fieldToMatch;

    /**
     * @param string $fieldToMatch O nome do outro campo que este deve ser igual (ex: 'password')
     */
    public function __construct(string $fieldToMatch)
    {
        $this->fieldToMatch = $fieldToMatch;
    }

    public function validate(string $attribute, mixed $value, array $allData = []): ?string
    {
        // Se a pessoa não preencheu, Required que barra
        if ($value === null || $value === '') {
            return null;
        }

        $otherFieldValue = $allData[$this->fieldToMatch] ?? null;

        if ($value !== $otherFieldValue) {
            return "O campo {$attribute} não confere com o campo {$this->fieldToMatch}.";
        }

        return null;
    }
}
