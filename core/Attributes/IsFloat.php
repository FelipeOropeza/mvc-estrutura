<?php

declare(strict_types=1);

namespace Core\Attributes;

use Attribute;
use Core\Contracts\ValidationRule;

#[Attribute]
class IsFloat implements ValidationRule
{
    private int $precision;
    private int $scale;

    /**
     * @param int $precision Quantidade TOTAL de números na casa
     * @param int $scale Quantidade de números nas casas DECIMAIS (após a vírgula/ponto)
     * Ex: FLOAT/DECIMAL(5, 2) => Máximo 999.99
     */
    public function __construct(int $precision = 8, int $scale = 2)
    {
        $this->precision = $precision;
        $this->scale = $scale;
    }

    public function validate(string $attribute, mixed $value, array $allData = []): ?string
    {
        if ($value === null || $value === '') {
            return null; // A obrigatoriedade é papel do #[Required]
        }

        // Se o valor contiver vírgula, trocamos por ponto p/ facilitar o PHP
        $valStr = str_replace(',', '.', (string) $value);

        if (!is_numeric($valStr)) {
            return "O campo {$attribute} deve ser um número decimal válido.";
        }

        $parts = explode('.', ltrim($valStr, '-'));
        $intPart = $parts[0] ?? '';
        $decPart = $parts[1] ?? '';

        $maxIntDigits = $this->precision - $this->scale;

        if (strlen($intPart) > $maxIntDigits) {
            return "O campo {$attribute} não pode exceder {$maxIntDigits} dígitos inteiros.";
        }

        if (strlen($decPart) > $this->scale) {
            return "O campo {$attribute} não pode ter mais que {$this->scale} casas decimais.";
        }

        return null; // O campo é um float e respeita as regras (precisão e escala)
    }
}
