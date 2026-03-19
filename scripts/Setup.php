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

        // Agora o DB é obrigatório via .env, então criamos o arquivo siliciosamente
        self::installDotenv();

        self::cleanup();

        echo "\n\033[1;32m✅ Instalação arquitetônica concluída com sucesso!\033[0m\n";
        echo "\033[0m   Para continuar, acesse e execute: \033[36mphp forge\033[0m\n";
        echo "\033[1;34m━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\033[0m\n\n";
    }

    private static function clearScreen(): void
    {
        // Limpa a tela inteira do terminal formatando visualmente limpo
        echo "\033[2J\033[;H";
    }

    private static function installDotenv(): void
    {
        echo "\n📦 Gerando arquivo de configuração local (.env)...\n";
        $envExample = __DIR__ . '/../.env.example';
        $envFile = __DIR__ . '/../.env';

        if (file_exists($envExample) && !file_exists($envFile)) {
            copy($envExample, $envFile);
            echo "✅ Arquivo '.env' gerado com sucesso! Lembre-se de configurar sua senha lá.\n";
        }
    }

    private static function cleanup(): void
    {
        // Limpa o próprio composer.json removendo o script post-create para não rodar mais
        $composerJson = __DIR__ . '/../composer.json';
        if (file_exists($composerJson)) {
            $data = json_decode(file_get_contents($composerJson), true);

            // Remove trigger
            if (isset($data['scripts']['post-create-project-cmd'])) {
                unset($data['scripts']['post-create-project-cmd']);
            }

            // Remove o auto-load do nosso espaço instalador pra não ficar lixo pro user final
            if (isset($data['autoload']['psr-4']['Scripts\\'])) {
                unset($data['autoload']['psr-4']['Scripts\\']);
            }

            // Altera meta infos nativas pro user
            $data['name'] = 'meu-projeto/app';
            $data['description'] = 'Um projeto baseado no MVC-Base esqueleto.';

            // Salva formatado limpo
            $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            file_put_contents($composerJson, $json);
        }

        // Rodando dump-autoload final pro composer reconhecer que a pasta Scripts não existe mais
        echo "\n\033[90m⚙️  Recarregando autoloader para o novo caminho formatado...\033[0m\n";
        passthru('composer dump-autoload -q');

        // E auto-deleta o script Setup! Magia 🪄
        @unlink(__FILE__);
        @rmdir(__DIR__);
    }
}
