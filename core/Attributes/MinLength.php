<?php

declare(strict_types=1);

namespace Core\Attributes;

use Attribute;
use Core\Contracts\ValidationRule;

#[Attribute]
class MinLength implements ValidationRule
{
    private int $min;
    public function __construct(int $min)
    {
        $this->min = $min;
    }

    public function validate(string $attribute, mixed $value, array $allData = []): ?string
    {
        if (mb_strlen((string)$value, 'UTF-8') < $this->min) {
            return "O campo {$attribute} precisa ter no mínimo {$this->min} caracteres.";
        }
        return null;
    }
}
