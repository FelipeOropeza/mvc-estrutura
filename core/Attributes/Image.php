<?php

declare(strict_types=1);

namespace Core\Attributes;

use Attribute;
use Core\Contracts\ValidationRule;
use Core\Http\UploadedFile;

#[Attribute]
class Image implements ValidationRule
{
    private int $maxSize;
    private array $allowedMimes;
    private ?string $message;

    /**
     * @param int $maxSizeMB Tamanho máximo em Megabytes (padrão 2MB)
     * @param array $mimes Lista opcional de mimetypes da imagem suportados
     * @param string|null $message Mensagem customizada de erro
     */
    public function __construct(int $maxSizeMB = 2, array $mimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'], ?string $message = null)
    {
        $this->maxSize = $maxSizeMB * 1024 * 1024;
        $this->allowedMimes = $mimes;
        $this->message = $message;
    }

    public function validate(string $attribute, mixed $value, array $allData = []): ?string
    {
        if ($value === null) {
            return null; // A obrigatoriedade é garantida pelo atributo #[Required]
        }

        if (!$value instanceof UploadedFile) {
            return $this->message ?? "O campo {$attribute} não contém uma imagem ou arquivo válido.";
        }

        if (!$value->isValid()) {
            return "Erro ao processar o upload da imagem {$attribute}, verifique o formato ou erro de rede.";
        }

        // Valida Tamanho
        if ($value->getSize() > $this->maxSize) {
            $maxMb = $this->maxSize / 1024 / 1024;
            return "A imagem {$attribute} não pode exceder {$maxMb}MB.";
        }

        // Tenta garantir que o MIME real é de Imagem usando as ferramentas internas do PHP
        $actualMime = $value->getClientMimeType();
        if (class_exists('\finfo')) {
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $actualMime = $finfo->file($value->getPathname());
        }

        // Valida Tipo de Mime Real da imagem
        if (!in_array($actualMime, $this->allowedMimes)) {
            return "O arquivo de imagem em {$attribute} deve ser de um formato suportado como " . implode(', ', $this->allowedMimes) . ". Recebido: $actualMime";
        }

        return null; // Sucesso
    }
}
