<?php

namespace Core\Attributes;
use Attribute;
use Core\Contracts\ValidationRule;

#[Attribute]
class MaxLength implements ValidationRule
{
    private int $max;
    public function __construct(int $max) { $this->max = $max; }

    public function validate(string $attribute, mixed $value): ?string
    {
        if (mb_strlen((string)$value, 'UTF-8') > $this->max) {
            return "O campo {$attribute} precisa ter no máximo {$this->max} caracteres.";
        }
        return null;
    }
}
