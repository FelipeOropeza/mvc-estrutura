<?php

declare(strict_types=1);

namespace Core\Providers;

use Core\Support\ServiceProvider;
use Core\Database\Connection;

class DatabaseServiceProvider extends ServiceProvider
{
    /**
     * Define como Instanciar nossa classe Mestre de Database
     */
    public function register(): void
    {
        // Ensina que quando algúem quiser "A conexão ativa com banco", ele te dá 
        // e ele MESMO cuida das senhas que vem do .env sem manchar o código local.
        $this->app->singleton(Connection::class, function ($app) {
            return Connection::getInstance(); // Nosso facade singleton da versão 1
        });
    }

    public function boot(): void
    {
        // Futuro local para configurar "Foreign Key constraints" do SQLite
        // Ou mudar dinamicamente fuso horário da conexão com base no Request
    }
}
