<?php

namespace Core\View;

class PhpEngine implements EngineInterface
{
    private string $viewPath;

    public function __construct(string $viewPath)
    {
        $this->viewPath = rtrim($viewPath, '/\\');
    }

    public function render(string $view, array $data = []): void
    {
        // Se a View não terminar em .php, adicionamos
        if (!str_ends_with($view, '.php')) {
            $view .= '.php';
        }

        $fullPath = $this->viewPath . DIRECTORY_SEPARATOR . $view;

        if (file_exists($fullPath)) {
            // Extrai as variaveis `$data['nome']` vira `$nome`
            extract($data);
            require $fullPath;
        }
        else {
            http_response_code(500);
            echo "Erro: View '{$view}' não foi encontrada no caminho '{$this->viewPath}'.";
        }
    }
}
