<?php

namespace Scripts;

class Setup
{
    public static function postCreateProject(): void
    {
        echo "\n";
        echo "=============================================\n";
        echo "Bem-vindo ao Forge MVC Base!\n";
        echo "=============================================\n\n";

        $fp = fopen('php://stdin', 'r');

        // Pergunta 1: Twig
        echo "1) Você gostaria de usar o Twig como motor de templates pricialmente? [y/N]: ";
        $inputTwig = strtolower(trim(stream_get_line($fp, 1024, PHP_EOL)));

        if ($inputTwig === 'y' || $inputTwig === 'yes') {
            self::installTwig();
        }
        else {
            echo "\nMantendo PHP puro como motor de templates.\n";
        }

        // Pergunta 2: Banco de Dados (.env)
        echo "\n2) Você gostaria de usar a biblioteca vlucas/phpdotenv para criar e carregar variáveis de um arquivo .env? [Y/n]: ";
        $inputEnv = strtolower(trim(stream_get_line($fp, 1024, PHP_EOL)));

        // Padrão sim (entende Enter e 'y')
        if ($inputEnv === '' || $inputEnv === 'y' || $inputEnv === 'yes') {
            self::installDotenv();
        }
        else {
            echo "\nIgnorando phpdotenv. As conexões de banco de dados usarão mysql em localhost fixo confugirado em config/database.php.\n";
        }

        self::installDatabaseBase();

        self::cleanup();

        echo "\nInstalação concluída! Digite 'forge' para ver os comandos disponíveis!\n";
        echo "=============================================\n";
    }

    private static function installTwig(): void
    {
        echo "\nInstalando e configurando o Twig...\n";

        // Instala a biblioteca
        passthru('composer require twig/twig');

        // Altera o config para usar twig
        $configFile = __DIR__ . '/../config/app.php';
        if (file_exists($configFile)) {
            $content = file_get_contents($configFile);
            $content = str_replace("'view_engine' => 'php'", "'view_engine' => 'twig'", $content);
            file_put_contents($configFile, $content);
        }

        // Renomeia a view base de exemplo para .twig
        $viewFile = __DIR__ . '/../app/Views/home.php';
        $twigFile = __DIR__ . '/../app/Views/home.twig';
        if (file_exists($viewFile)) {
            // Um HTML simples em formato Twig
            $twigContent = '<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>{{ title|default("MVC") }}</title>
</head>
<body>
    <h1>{{ title }}</h1>
    <p>Olá, {{ name }}! Bem-vindo(a) à sua estrutura MVC com Twig.</p>
</body>
</html>';
            file_put_contents($twigFile, $twigContent);
            unlink($viewFile); // apaga o php antigo
        }

        // Atualiza os templates do Forge
        self::updateForgeTemplates();

        echo "\n✅ Twig configurado e ativado como motor oficial de templates!\n";
    }

    private static function updateForgeTemplates(): void
    {
        $stubFile = __DIR__ . '/../core/Console/Templates/view.stub';
        if (file_exists($stubFile)) {
            $twigStub = '<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Sua View</title>
</head>
<body>
    <h1>Nova View: {{ "{{ name }}" }}</h1>
</body>
</html>';
            file_put_contents($stubFile, $twigStub);
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
