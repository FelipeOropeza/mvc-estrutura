<?php

namespace Core\Console;

class Kernel
{
    private array $config;

    public function __construct()
    {
        // Carrega o arquivo de configuração
        $this->config = require __DIR__ . '/../../config/app.php';
    }

    public function handle(array $args): void
    {
        array_shift($args); // Remove o nome do script

        if (empty($args)) {
            $this->showHelp();
            exit(1);
        }

        $command = $args[0];

        switch ($command) {
            case 'make:controller':
                $this->makeController($args);
                break;
            case 'make:model':
                $this->makeModel($args);
                break;
            case 'make:view':
                $this->makeView($args);
                break;
            case 'setup:engine':
                $this->setupEngine($args);
                break;
            default:
                echo "Erro: Comando não reconhecido: '$command'\n";
                $this->showHelp();
                exit(1);
        }
    }

    private function showHelp(): void
    {
        echo "MVC Base Console\n";
        echo "=================\n";
        echo "Uso: forge [comando] ou php forge [comando]\n\n";
        echo "Comandos disponíveis:\n";
        echo "  make:controller <Nome>   Cria um novo Controller\n";
        echo "  make:model <Nome>        Cria um novo Model\n";
        echo "  make:view <Nome>         Cria uma nova View automaticamente na extensão correta\n";
        echo "  setup:engine <php|twig>  Muda o motor padrão do projeto e limpa views não utilizadas\n";
    }

    private function makeController(array $args): void
    {
        if (!isset($args[1])) {
            echo "Erro: Forneça o nome. Ex: make:controller UsuarioController\n";
            exit(1);
        }

        $name = $args[1];
        if (!str_ends_with($name, 'Controller')) {
            $name .= 'Controller';
        }

        $path = $this->config['paths']['controllers'] . '/' . $name . '.php';
        $content = $this->renderTemplate('controller', ['{{name}}' => $name]);
        $this->createFile($path, $content, "Controller '$name'");
    }

    private function makeModel(array $args): void
    {
        if (!isset($args[1])) {
            echo "Erro: Forneça o nome. Ex: make:model Usuario\n";
            exit(1);
        }

        $name = $args[1];
        $dir = $this->config['paths']['models'];
        $path = $dir . '/' . $name . '.php';

        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $content = $this->renderTemplate('model', ['{{name}}' => $name]);
        $this->createFile($path, $content, "Model '$name'");
    }

    private function makeView(array $args): void
    {
        if (!isset($args[1])) {
            echo "Erro: Forneça o nome. Ex: make:view usuario/perfil\n";
            exit(1);
        }

        $name = $args[1];

        // Pega do seu config/app.php o motor base atual ('php' ou 'twig')
        $engine = $this->config['app']['view_engine'] ?? 'php';
        $extension = $engine === 'twig' ? '.twig' : '.php';

        // Anexa a extensão baseada no motor dinamicamente ao nome se não tem
        if (!str_ends_with($name, $extension) && !str_ends_with($name, '.html')) {
            // Remove se a pessoa digitou a outra sem querer
            $name = str_replace(['.php', '.twig'], '', $name);
            $name .= $extension;
        }

        $path = $this->config['paths']['views'] . '/' . $name;
        $dir = dirname($path);

        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $content = $this->renderTemplate('view', ['{{name}}' => $name]);
        $this->createFile($path, $content, "View '$name'");
    }

    private function setupEngine(array $args): void
    {
        if (!isset($args[1]) || !in_array($args[1], ['php', 'twig'])) {
            echo "Erro: Forneça o motor. Ex: setup:engine twig\n";
            exit(1);
        }

        $engine = $args[1];
        $configFile = realpath(__DIR__ . '/../../config/app.php');
        $content = file_get_contents($configFile);

        // Troca valor da chave no config/app.php
        $content = preg_replace("/'view_engine'\s*=>\s*'[^']+'/", "'view_engine' => '$engine'", $content);
        file_put_contents($configFile, $content);

        // Limpa a tela exemplo incompatível
        $viewsPath = rtrim($this->config['paths']['views'], '/');
        if ($engine === 'twig') {
            if (file_exists("$viewsPath/home.php"))
                unlink("$viewsPath/home.php");
            echo "✅ Motor da View comutado para TWIG.\n";
            echo "   (Execute 'composer require twig/twig' no terminal se ainda não instalou!).\n";
        }
        else {
            if (file_exists("$viewsPath/home.twig"))
                unlink("$viewsPath/home.twig");
            echo "✅ Motor da View comutado para nativo PHP.\n";
        }
    }

    private function renderTemplate(string $templateName, array $replacements): string
    {
        $templatePath = $this->config['paths']['templates'] . '/' . $templateName . '.stub';

        if (!file_exists($templatePath)) {
            echo "Erro: Template não encontrado em: $templatePath\n";
            exit(1);
        }

        $content = file_get_contents($templatePath);

        foreach ($replacements as $search => $replace) {
            $content = str_replace($search, $replace, $content);
        }

        return $content;
    }

    private function createFile(string $path, string $content, string $type): void
    {
        if (file_exists($path)) {
            echo "Erro: O $type já existe.\n";
            exit(1);
        }

        file_put_contents($path, $content);

        // Formara o caminho para exibir de forma limpa no console
        $relativePath = str_replace(realpath(__DIR__ . '/../../') . DIRECTORY_SEPARATOR, '', realpath($path) ?: $path);
        // Em casos que o arquivo seja recém criado, fallback para o caminho cru limpo
        $relativePath = str_replace('\\', '/', trim(str_replace(str_replace('\\', '/', __DIR__ . '/../../'), '', str_replace('\\', '/', $path)), '/'));

        echo "✅ $type criado em: $relativePath\n";
    }
}
