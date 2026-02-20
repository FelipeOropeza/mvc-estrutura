<?php

namespace Scripts;

class Setup
{
    public static function postCreateProject(): void
    {
        self::clearScreen();
        echo "\n";
        echo "\033[1;34m━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\033[0m\n";
        echo "   \033[1;37m✨  BEM-VINDO AO FORGE MVC BASE INITIALIZER  ✨\033[0m\n";
        echo "\033[1;34m━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\033[0m\n\n";

        $engineChoice = self::askConfig(
            "Qual motor de templates você deseja utilizar no projeto?",
        [
            '1' => 'PHP Nativo (Mais rápido e sem dependências extras)',
            '2' => 'Twig Engine (Sintaxe frontend enxuta, estilo Blade/Vue)'
        ],
            '1'
        );

        if ($engineChoice === '2') {
            self::setupEngineChoice('twig');
        }
        else {
            self::setupEngineChoice('php');
        }

        $envChoice = self::askConfig(
            "Deseja instalar suporte flexível para Banco de Dados (.env)?",
        [
            'y' => 'Sim, instale o pacote phpdotenv (Recomendado na web)',
            'n' => 'Não, vou configurar direto pelo arquivo antigo PHP puro'
        ],
            'y'
        );

        if (strtolower($envChoice) === 'y') {
            self::installDotenv();
        }
        else {
            echo "\n\033[33mℹ️  Ignorando phpdotenv. Conexões de DB usarão config local.\033[0m\n";
        }

        self::installDatabaseBase();
        self::cleanup();

        echo "\n\033[1;32m✅ Instalação arquitetônica concluída com sucesso!\033[0m\n";
        echo "\033[0m   Para continuar, acesse e execute: \033[36mphp forge\033[0m\n";
        echo "\033[1;34m━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\033[0m\n\n";
    }

    private static function askConfig(string $question, array $options, string $defaultKey): string
    {
        echo "\033[32m?\033[0m \033[1m{$question}\033[0m\n";
        foreach ($options as $key => $desc) {
            $isDefault = ($key === $defaultKey) ? " \033[33m(padrão)\033[0m" : "";
            echo "  \033[36m[{$key}]\033[0m {$desc}{$isDefault}\n";
        }

        echo "\n  \033[1;32m❯\033[0m ";
        $fp = fopen('php://stdin', 'r');
        $choice = trim(stream_get_line($fp, 1024, PHP_EOL));

        if ($choice === '') {
            $choice = $defaultKey;
        }

        echo "\n";
        return $choice;
    }

    private static function clearScreen(): void
    {
        // Limpa a tela inteira do terminal formatando visualmente limpo
        echo "\033[2J\033[;H";
    }

    private static function setupEngineChoice(string $engine): void
    {
        $configFile = __DIR__ . '/../config/app.php';
        $viewsPath = rtrim(__DIR__ . '/../app/Views', '/');

        if ($engine === 'twig') {
            echo "\n⚙️  Instalando e configurando o Twig...\n";
            // Instala a biblioteca
            passthru('composer require twig/twig');

            // Altera o config
            if (file_exists($configFile)) {
                $content = file_get_contents($configFile);
                $content = preg_replace("/'view_engine'\s*=>\s*'[^']+'/", "'view_engine' => 'twig'", $content);
                file_put_contents($configFile, $content);
            }

            // Exclui a view PHP para usar a home.twig pronta e bonitona
            if (file_exists("$viewsPath/home.php")) {
                unlink("$viewsPath/home.php");
            }
            echo "✅ Twig ativado como motor oficial de templates!\n";
        }
        else {
            echo "\n⚙️  Ativando PHP nativo como motor de templates.\n";
            // Exclui a view twig para manter o repositório limpo a favor do home.php
            if (file_exists("$viewsPath/home.twig")) {
                unlink("$viewsPath/home.twig");
            }
            echo "✅ Motor nativo ativado com sucesso!\n";
        }
    }

    private static function installDotenv(): void
    {
        echo "\n📦 Instalando 'vlucas/phpdotenv' suporte para banco de dados flexível...\n";
        passthru('composer require vlucas/phpdotenv');

        // Copia o .env.example e cria o definitivo
        $envExample = __DIR__ . '/../.env.example';
        $envFile = __DIR__ . '/../.env';

        if (file_exists($envExample) && !file_exists($envFile)) {
            copy($envExample, $envFile);
            echo "\n✅ Arquivo '.env' gerado com sucesso! Lembre-se de configurar sua senha lá.\n";
        }
    }

    private static function installDatabaseBase(): void
    {
        // Se phpdotenv instalou, precisa adicionar o código no index.php para carregar o .env
        $indexPath = __DIR__ . '/../public/index.php';
        if (file_exists($indexPath)) {
            $index = file_get_contents($indexPath);

            // Só insere se não inseriu antes
            if (strpos($index, 'Dotenv\Dotenv::createImmutable') === false) {
                $dotenvLoader = "\nif (class_exists('Dotenv\Dotenv') && file_exists(__DIR__ . '/../.env')) {
    \$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
    \$dotenv->load();
}\n";
                // Injeta o loader do dotenv logo após o autoloader do composer
                $index = str_replace("require_once __DIR__ . '/../vendor/autoload.php';", "require_once __DIR__ . '/../vendor/autoload.php';" . $dotenvLoader, $index);
                file_put_contents($indexPath, $index);
            }
        }
    }

    private static function cleanup(): void
    {
        // Limpa o próprio composer.json removendo o script post-create para não rodar mais
        $composerJson = __DIR__ . '/../composer.json';
        if (file_exists($composerJson)) {
            $data = json_decode(file_get_contents($composerJson), true);
            if (isset($data['scripts']['post-create-project-cmd'])) {
                unset($data['scripts']['post-create-project-cmd']);

                // Salva formatado limpo
                $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                file_put_contents($composerJson, $json);
            }
        }

        // E auto-deleta o script Setup! Magia 🪄
        @unlink(__FILE__);
        @rmdir(__DIR__);
    }
}
