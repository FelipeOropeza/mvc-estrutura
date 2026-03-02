<?php

declare(strict_types=1);

namespace Core\Providers;

use Core\Support\ServiceProvider;
use Core\Routing\Router;
use Core\Routing\AttributeRouteScanner;

class RoutingServiceProvider extends ServiceProvider
{
    /**
     * Onde e como o Router existe globalmente
     */
    public function register(): void
    {
        // Ensina o Sistema a fabricar APENAS 1 Router pra todo mundo
        $this->app->singleton(Router::class, function ($app) {
            return new Router();
        });
    }

    /**
     * Tendo o Router criado, incluímos as rotas definidas pelo dev
     */
    public function boot(): void
    {
        // Como o app já registrou o Router ( acima ), 
        // a gente Puxa ele para fora usando o Container!
        $router = $this->app->get(Router::class);

        $basePath = $this->app->get('path.base');
        $cacheFile = $basePath . '/.cache/routes.php';

        if (file_exists($cacheFile)) {
            // Carrega rotas cacheadas em memória, evitando Reflection e Scans
            $router->setRoutes(require $cacheFile);
        } else {
            // Carrega o arquivo padrão de rotas web.php se existir
            if (file_exists($basePath . '/routes/web.php')) {
                // "Injeta" a váriavel $router que o web.php espera ler!
                require $basePath . '/routes/web.php';
            }

            // Escaneia a pasta app/Controllers buscando rotas com PHP Attributes
            $scanner = new AttributeRouteScanner();
            $scanner->scan($router, $basePath . '/app/Controllers', 'App\\Controllers\\');
        }
    }
}
