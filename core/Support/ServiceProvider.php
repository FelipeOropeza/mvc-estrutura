<?php

declare(strict_types=1);

namespace Core\Support;

abstract class ServiceProvider
{
    /**
     * A instância central da aplicação/recipiente de dependências.
     */
    protected Container $app;

    public function __construct(Container $app)
    {
        $this->app = $app;
    }

    /**
     * Registra dependências no container ANTES que a aplicação execute rotas ou processe ações.
     * Serve exclusivamente para ligar botões abstratos a implementações concretas (Bindings).
     */
    public function register(): void
    {
        // Deixado vazio para providers que só usem o método boot()
    }

    /**
     * Executa qualquer lógica necessária APÓS todos os Providers registrarem suas capacidades.
     * Aqui é seguro acessar qualquer classe do Container.
     */
    public function boot(): void
    {
        // Deixado vazio para providers que só usem o método register()
    }
}
