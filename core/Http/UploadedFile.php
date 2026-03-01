<?php

declare(strict_types=1);

namespace Core\Http;

use SplFileInfo;

class UploadedFile extends SplFileInfo
{
    private string $originalName;
    private string $mimeType;
    private int $error;
    private int $size;

    public function __construct(string $path, string $originalName, string $mimeType = null, int $error = null, int $size = 0)
    {
        parent::__construct($path);

        $this->originalName = $originalName;
        $this->mimeType = $mimeType ?? 'application/octet-stream';
        $this->error = $error ?? UPLOAD_ERR_OK;
        $this->size = $size;
    }

    public function getOriginalName(): string
    {
        return $this->originalName;
    }

    public function getClientMimeType(): string
    {
        return $this->mimeType;
    }

    public function getError(): int
    {
        return $this->error;
    }

    public function isValid(): bool
    {
        return $this->error === UPLOAD_ERR_OK;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    /**
     * Move the uploaded file to a new location.
     * This method will be expanded later to use the Storage system.
     */
    public function moveTo(string $targetPath): bool
    {
        if (!$this->isValid() || !is_uploaded_file($this->getPathname())) {
            throw new \Exception("Cannot move an invalid or non-uploaded file.");
        }

        $targetDir = dirname($targetPath);
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        if (move_uploaded_file($this->getPathname(), $targetPath)) {
            return true;
        }

        throw new \Exception("Failed to move uploaded file to {$targetPath}.");
    }

    /**
     * Armazena o arquivo no disco selecionado (gerado um nome único aleatorio se não informado).
     * 
     * @param string $path Diretório de destino, ex: 'avatars'
     * @param string $disk Disco configurado no StorageManager, ex: 'local'
     * @return string O caminho final salvo
     */
    public function store(string $path, string $disk = 'local'): string
    {
        if (!$this->isValid() || !is_uploaded_file($this->getPathname())) {
            throw new \Exception("Cannot store an invalid or non-uploaded file.");
        }

        $extension = pathinfo($this->getOriginalName(), PATHINFO_EXTENSION);
        $filename = uniqid('file_', true) . ($extension ? '.' . $extension : '');

        $targetPath = trim($path, '/') . '/' . $filename;

        $storage = \Core\Storage\StorageManager::disk($disk);
        return $storage->putFile($targetPath, $this->getPathname());
    }
}
