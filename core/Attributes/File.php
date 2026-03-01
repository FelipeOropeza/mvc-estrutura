<?php

declare(strict_types=1);

namespace Core\Attributes;

use Attribute;
use Core\Contracts\ValidationRule;
use Core\Http\UploadedFile;

#[Attribute]
class File implements ValidationRule
{
    private int $maxSize;
    private array $mimes;
    private ?string $message;

    /**
     * @param int $maxSize Tamanho máximo em bytes (ex: 2048 * 1024 para 2MB)
     * @param array $mimes Lista opcional de mimetypes válidos
     * @param string|null $message Mensagem customizada
     */
    public function __construct(int $maxSize = 2097152, array $mimes = [], ?string $message = null)
    {
        $this->maxSize = $maxSize;
        $this->mimes = $mimes;
        $this->message = $message;
    }

    public function validate(string $attribute, mixed $value, array $allData = []): ?string
    {
        if ($value === null) {
            return null; // A obrigatoriedade é garantida pelo atributo #[Required]
        }

        if (!$value instanceof UploadedFile) {
            return $this->message ?? "O campo {$attribute} não é um arquivo válido.";
        }

        if (!$value->isValid()) {
            return "Ocorreu um erro no envio do arquivo {$attribute}: Código de erro " . $value->getError();
        }

        if ($value->getSize() > $this->maxSize) {
            return "O arquivo {$attribute} não pode ser maior que " . ($this->maxSize / 1024 / 1024) . "MB.";
        }

        if (!empty($this->mimes) && !in_array($value->getClientMimeType(), $this->mimes)) {
            return "O arquivo {$attribute} não é de um tipo suportado. Válidos: " . implode(', ', $this->mimes) . ".";
        }

        return null;
    }
}
