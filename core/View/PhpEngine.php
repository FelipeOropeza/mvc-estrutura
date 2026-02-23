<?php

namespace Core\View;

class PhpEngine implements EngineInterface
{
    private string $viewPath;

    // 5. Compartilhamento Global (Variáveis injetadas para TODAS as views)
    public static array $shared = [];

    // Sistema de Layout e Sections (Herança)
    private ?string $layout = null;
    private array $layoutData = [];
    private array $sections = [];
    private ?string $currentSection = null;

    public function __construct(string $viewPath)
    {
        $this->viewPath = rtrim($viewPath, '/\\');
    }

    /**
     * 5. Injeta Dado Global
     */
    public static function share(string $key, mixed $value): void
    {
        self::$shared[$key] = $value;
    }

    public function render(string $view, array $data = []): void
    {
        $fullPath = $this->resolvePath($view);

        if (file_exists($fullPath)) {
            // Mescla dados globais (share) com dados do Controller ($data)
            $data = array_merge(self::$shared, $data);
            extract($data);

            // Inicia captura do "miolo" (A view propriamente dita)
            ob_start();
            require $fullPath;
            $content = ob_get_clean();

            // Proteção para injetar o fluxo normal se não for especificado @section('content')
            if (!isset($this->sections['content'])) {
                $this->sections['content'] = $content;
            }

            // 1. Resolve Layouts: Se essa view solicitou um Layout ('main'), ele assume!
            if ($this->layout !== null) {
                $layoutPath = $this->resolvePath($this->layout);
                // Permite a view filha enviar dados especificos de header/title extra para o Layout
                $layoutMergedData = array_merge($data, $this->layoutData);

                if (file_exists($layoutPath)) {
                    extract($layoutMergedData);
                    ob_start();
                    require $layoutPath;
                    echo ob_get_clean();
                }
                else {
                    echo "Erro: Layout '{$this->layout}' não encontrado.";
                }
            }
            else {
                // Sem layout mestre, só imprime a própria view formatada
                echo $content;
            }
        }
        else {
            http_response_code(500);
            echo "Erro: View '{$view}' não encontrada em '{$this->viewPath}'.";
        }
    }

    /**
     * 1. Define qual Layout MASTER essa view deve usar.
     */
    public function layout(string $layout, array $data = []): void
    {
        $this->layout = $layout;
        $this->layoutData = $data;
    }

    /**
     * 1. Inicia um recheio de seção customizado (Ex: Scripts do rodapé extra)
     */
    public function section(string $name): void
    {
        $this->currentSection = $name;
        ob_start();
    }

    /**
     * 1. Termina e enfileira uma seção customizada.
     */
    public function endSection(): void
    {
        if ($this->currentSection) {
            $this->sections[$this->currentSection] = ob_get_clean();
            $this->currentSection = null;
        }
    }

    /**
     * 1. Imprime os blocos de seção inseridos pela view filha
     * (Equivalente ao @yield do Blade).
     */
    public function renderSection(string $name): void
    {
        echo $this->sections[$name] ?? '';
    }

    /**
     * 2. Inclui outro arquivo inteiro (Componente / Parcial / Navbar / Footer)
     */
    public function include(string $view, array $data = []): void
    {
        $fullPath = $this->resolvePath($view);
        if (file_exists($fullPath)) {
            // Um partial tem capacidade de herdar os compartilhamentos Globais e variaveis locais!
            $data = array_merge(self::$shared, $data);
            extract($data);
            require $fullPath;
        }
        else {
            echo "<!-- Partial '{$view}' não encontrado -->";
        }
    }

    /**
     * Normalizador de Caminhos
     */
    private function resolvePath(string $view): string
    {
        if (!str_ends_with($view, '.php')) {
            $view .= '.php';
        }
        return $this->viewPath . DIRECTORY_SEPARATOR . $view;
    }
}
