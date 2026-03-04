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
     * Caso o \$data enviado seja null, automaticamente utiliza o payload enviado na Request via request()->all().
     *
     * @param array|null \$data Dados a serem validados contra a estrutura deste DTO.
     * @throws ValidationException
     */
    public function __construct(?array $data = null)
    {
        // 1. Hook de Autorização (Gatekeeping). Se falso, encerra o ciclo instantaneamente com 403.
        if (!$this->authorize()) {
            // Lança uma exceção genérica ou joga um abort(403) global se o framework suportar
            throw new \Exception("Acesso negado ou não autorizado para essa requisição.", 403);
        }

        // Se a pessoa não enviou o array pra preencher o DTO, pegamos da Request global (via helper ou superglobal)
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
                $this->$key = $value;
            }
        }
    }

    /**
     * Converte o objeto DTO finalizado de volta em um array.
     * Extremamente útil para jogar dentro do DB/Model ex: \$user->insert(\$dto->toArray());
     *
     * @return array
     */
    public function toArray(): array
    {
        // Pega as properties públicas deste objeto
        return get_object_vars($this);
    }
}
