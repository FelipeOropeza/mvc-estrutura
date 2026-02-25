<?php

namespace Core\View;

class TwigEngine implements EngineInterface
{
    private $twig;
    private string $viewPath;

    public function __construct(string $viewPath)
    {
        $this->viewPath = rtrim($viewPath, '/\\');

        // Verifica se a classe do Twig existe (Ou seja, se foi instalada no Composer)
        if (!class_exists('\Twig\Environment')) {
            http_response_code(500);
            echo "Erro Crítico: O motor de template selecionado é o Twig, mas a biblioteca não está instalada.<br>";
            echo "Por favor, rode: <code>composer require twig/twig</code> no terminal do projeto.";
            exit;
        }

        $loader = new \Twig\Loader\FilesystemLoader($this->viewPath);

        // Em producao, seria bom ter um diretorio de cache. 
        // Para inicio/aprendizado, deixamos desligado.
        $this->twig = new \Twig\Environment($loader, [
            'cache' => false,
        ]);
    }

    public function render(string $view, array $data = []): string
    {
        // View padrao do twig se nao passar extensao
        if (!str_ends_with($view, '.twig') && !str_ends_with($view, '.html')) {
            $view .= '.twig';
        }

        try {
            return $this->twig->render($view, $data);
        }
        catch (\Exception $e) {
            http_response_code(500);
            return "Erro de Template Twig: " . $e->getMessage();
        }
    }
}
