<?php

declare(strict_types=1);

namespace App\Providers;

use Core\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Registre aqui bindings adicionais para o sistema usando `$this->app`
     * (Ex: Conectar a uma API terceira tipo Stripe, Ou declarar variavéis do container)
     */
    public function register(): void
    {
        // 
    }

    /**
     * Você deve executar a inicialização/configuração de serviços que dependem de outros serviços.
     * Use esse lugar para setar Layouts da empresa, ou forçar limites/tamanhos máximos no PHP custom.
     */
    public function boot(): void
    {
        // 
    }
}
