<?php

namespace Core\Validation;

use ReflectionClass;
use ReflectionProperty;
use Core\Contracts\ValidationRule;

class Validator
{
    protected array $errors = [];
    protected array $data = [];

    /**
     * Valida os campos do objeto Request usando Atributos do PHP 8.
     * 
     * @param object $requestObject Um DTO ou objeto com propriedades mapeadas.
     * @param array $inputData Os dados $_POST ou JSON que vieram da requisicao bruta.
     * @return bool
     */
    public function validate(object $requestObject, array $inputData): bool
    {
        $reflection = new ReflectionClass($requestObject);
        $properties = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);

        foreach ($properties as $property) {
            $name = $property->getName();

            // Pega o valor enviado ou null se não existir
            $value = $inputData[$name] ?? null;

            // Atribui o valor ao objeto Request DTO
            $property->setValue($requestObject, $value);

            // Pega as regras em formato de Atributos do PHP 8
            $attributes = $property->getAttributes(ValidationRule::class , \ReflectionAttribute::IS_INSTANCEOF);

            foreach ($attributes as $attribute) {
                // Instancia e Roda a regra de Validação (Required, Email, etc)
                $rule = $attribute->newInstance();
                $error = $rule->validate($name, $value);

                if ($error !== null) {
                    $this->errors[$name][] = $error;
                }
            }

            // Se não deu erro, higienizamos o dado para guardar limpo
            if (!isset($this->errors[$name])) {
                $this->data[$name] = is_string($value) ? strip_tags($value) : $value;
            }
        }

        return empty($this->errors);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getValidatedData(): array
    {
        return $this->data;
    }
}
