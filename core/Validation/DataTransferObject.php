<?php

declare(strict_types=1);

namespace Core\Validation;

use Core\Exceptions\ValidationException;

abstract class DataTransferObject
{
    /**
     * Define se o usuário atual tem permissão para realizar esta requisição acoplada a este DTO.
     * Semelhante ao FormRequest do Laravel.
     * 
     * @return bool
     */
    protected function authorize(): bool
    {
        return true; // Por padrão, autoriza a todos. Sobrescreva nas classes filhas!
    }

    /**
     * Valida os dados usando os atributos de validação definidos nas propriedades da classe filha.
     * Caso o $data enviado seja null, automaticamente utiliza o payload enviado na Request via request()->all().
     *
     * @param array|null $data Dados a serem validados contra a estrutura deste DTO.
     * @throws ValidationException
     */
    public function __construct(?array $data = null)
    {
        // 1. Hook de Autorização (Gatekeeping). Se falso, encerra o ciclo instantaneamente com 403.
        if (!$this->authorize()) {
            throw new \Exception("Acesso negado ou não autorizado para essa requisição.", 403);
        }

        // Se a pessoa não enviou o array pra preencher o DTO, pegamos da Request global
        $inputData = $data ?? (\function_exists('request') ? request()->all() : $_REQUEST);

        $validator = new Validator();

        // O Validator avalia as propriedades desta instância e seus respectivos Attributes (Regras)
        $isValid = $validator->validate($this, $inputData);

        if (!$isValid) {
            $errors = $validator->getErrors();
            throw new ValidationException($errors, $inputData);
        }

        $validatedData = $validator->getValidatedData();

        // Atribui os dados validados e confiáveis às propriedades deste DTO de forma automática
        foreach ($validatedData as $key => $value) {
            if (property_exists($this, $key)) {
                $reflection = new \ReflectionProperty($this, $key);
                $type = $reflection->getType();

                // Se a propriedade tiver um tipo nativo (int, float, bool), tentamos o cast
                if ($type instanceof \ReflectionNamedType && $type->isBuiltin()) {
                    $typeName = $type->getName();
                    if ($value !== null && $value !== "") {
                        switch ($typeName) {
                            case 'int':
                                $value = (int) $value;
                                break;
                            case 'float':
                                $value = (float) str_replace(',', '.', (string)$value);
                                break;
                            case 'bool':
                                $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                                break;
                        }
                    }
                }

                // Usamos ReflectionProperty::setValue para suportar Asymmetric Visibility (PHP 8.4+)
                // Isso permite que o DTO tenha propriedades public private(set)
                $reflection->setValue($this, $value);
            }
        }
    }

    /**
     * Converte o objeto DTO finalizado de volta em um array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
