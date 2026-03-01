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

    /**
     * @param int $maxSizeMB Tamanho máximo em Megabytes (padrão 2MB)
     * @param array $mimes Lista opcional de mimetypes da imagem suportados
     */
    public function __construct(int $maxSizeMB = 2, array $mimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'])
    {
        $this->maxSize = $maxSizeMB * 1024 * 1024;
        $this->allowedMimes = $mimes;
    }

    public function validate(string $attribute, mixed $value, array $allData = []): ?string
    {
        if ($value === null) {
            return null; // A obrigatoriedade é garantida pelo atributo #[Required]
        }

        if (!$value instanceof UploadedFile) {
            return "O campo {$attribute} não contém uma imagem ou arquivo válido.";
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
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
                $actualMime = finfo_file($finfo, $value->getPathname());
                finfo_close($finfo);
            }
        }

        // Valida Tipo de Mime Real da imagem
        if (!in_array($actualMime, $this->allowedMimes)) {
            return "O arquivo de imagem em {$attribute} deve ser de um formato suportado como " . implode(', ', $this->allowedMimes) . ". Recebido: $actualMime";
        }

        return null; // Sucesso
    }
}
