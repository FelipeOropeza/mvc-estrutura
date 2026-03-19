<?php

declare(strict_types=1);

namespace Core\Providers;

use Core\Support\ServiceProvider;
use Core\View\EngineInterface;
use Core\View\PhpEngine;

class ViewServiceProvider extends ServiceProvider
{
    /**
     * Registra o motor de views no Container como Singleton,
     * para não abrir/ler os arquivos de configuração do disco em toda renderização
     */
    public function register(): void
    {
        $this->app->singleton(EngineInterface::class, function ($app) {
            $config = require $app->get('path.base') . '/config/app.php';
            $viewPath = $config['paths']['views'];

            return new PhpEngine($viewPath);
        });
    }

    public function boot(): void
    {
        // ...
    }
}
