<?php

declare(strict_types=1);

namespace Core\Storage;

class LocalAdapter
{
    protected string $basePath;

    public function __construct(string $basePath)
    {
        $this->basePath = rtrim($basePath, '/');
    }

    /**
     * Move an uploaded file to the local storage.
     */
    public function putFile(string $path, string $sourceTempPath): string
    {
        $targetPath = $this->basePath . '/' . ltrim($path, '/');
        $targetDir = dirname($targetPath);

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        if (move_uploaded_file($sourceTempPath, $targetPath)) {
            return $path;
        }

        throw new \Exception("Failed to move file to {$targetPath}");
    }

    /**
     * Check if a file exists.
     */
    public function exists(string $path): bool
    {
        return file_exists($this->basePath . '/' . ltrim($path, '/'));
    }

    /**
     * Delete a file.
     */
    public function delete(string $path): bool
    {
        $fullPath = $this->basePath . '/' . ltrim($path, '/');
        if (file_exists($fullPath)) {
            return unlink($fullPath);
        }
        return false;
    }
}
