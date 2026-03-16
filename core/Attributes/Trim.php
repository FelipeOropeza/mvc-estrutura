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
        if (!is_string($value)) {
            return $value;
        }

        // mb_trim está disponível a partir do PHP 8.4
        if (function_exists('mb_trim')) {
            return mb_trim($value);
        }

        return trim($value);
    }
}
