<?php

declare(strict_types=1);

namespace App\Providers;

use Core\Support\ServiceProvider;
use Symfony\Component\Mercure\Hub;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Jwt\LcobucciFactory;
use Symfony\Component\Mercure\Jwt\FactoryTokenProvider;

class MercureServiceProvider extends ServiceProvider
{
    /**
     * Registra o Hub do Mercure no container de serviços.
     * Isso permite injetar HubInterface em qualquer Controller ou Service.
     */
    public function register(): void
    {
        $this->app->singleton(HubInterface::class, function ($app) {
            // Buscamos as configurações que definimos no .env
            $url = env('MERCURE_URL', 'http://localhost:3000/.well-known/mercure');
            $publicUrl = env('MERCURE_PUBLIC_URL', $url);
            $jwt = env('MERCURE_PUBLISHER_JWT_KEY', 'aVerySecretPublisherKey123!');

            $jwtFactory = new LcobucciFactory($jwt);
            $provider = new FactoryTokenProvider($jwtFactory, publish: ['*']);

            return new Hub($url, $provider, $jwtFactory, $publicUrl);
        });

        // Caso queira usar um helper global ou apelido
        $this->app->instance('mercure', $this->app->get(HubInterface::class));
    }

    public function boot(): void
    {
        // Aqui você poderia registrar extensões de View para facilitar o uso no frontend, se necessário.
    }
}
