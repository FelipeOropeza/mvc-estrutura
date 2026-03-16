<?php

declare(strict_types=1);

namespace Core\Attributes;

use Attribute;
use Core\Contracts\Mutator;

/**
 * Atributo para limpar espaços em branco no início e fim de strings.
 * Uso: #[Trim] diretamente na propriedade do DTO.
 */
#[Attribute]
class Trim implements Mutator
{
    public function mutate(string $attribute, mixed $value): mixed
    {
        return is_string($value) ? trim($value) : $value;
    }
}
