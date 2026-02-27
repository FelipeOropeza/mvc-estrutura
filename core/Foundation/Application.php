<?php

declare(strict_types=1);

namespace Core\Foundation;

use Core\Support\Container;
use Core\Support\ServiceProvider;

class Application extends Container
{
    protected string $basePath;

    /**
     * @var ServiceProvider[]
     */
    protected array $loadedProviders = [];

    protected bool $booted = false;

    public function __construct(string $basePath)
    {
        // Define-se como singleton do container root também para retrocompatibilidade
        parent::__construct();

        $this->basePath = rtrim($basePath, '\/');

        // Registra os nomes principais como apelidos ("aliases") globais para a própria Aplicação
        $this->instance('app', $this);
        $this->instance(Container::class, $this);
        $this->instance(self::class, $this);

        // Bind básico de diretórios úteis para os Providers lerem arquivos físicos (ex: templates, rotas)
        $this->instance('path.base', $this->basePath);
        $this->instance('path.config', $this->basePath . DIRECTORY_SEPARATOR . 'config');
    }

    /**
     * Carrega as configurações dos providers listados no 'config/app.php'
     */
    public function registerConfiguredProviders(): void
    {
        $configPath = $this->get('path.config') . '/app.php';

        if (!file_exists($configPath)) {
            return;
        }

        $config = require $configPath;

        // Cadastra as configurações inteiras no Container pra não precisarmos reler o disco nas requisições.
        $this->instance('config', $config);

        $providers = $config['providers'] ?? [];

        foreach ($providers as $providerClass) {
            $this->register(new $providerClass($this));
        }
    }

    /**
     * Inicializa a parte de "Registro" (Ligações do Container) do Provider individual
     */
    public function register(ServiceProvider $provider): ServiceProvider
    {
        $provider->register();
        $this->loadedProviders[get_class($provider)] = $provider;

        // Se a aplicação já estiver viva ("booted") (ex: o usuário registrou um plugin de terceiros
        // no meio da requisição já em andamento), nós damos boot nele isoladamente:
        if ($this->booted) {
            $provider->boot();
        }

        return $provider;
    }

    /**
     * Executa a fase de "Boot" (inicialização concreta dos serviços dependentes)
     * de todos os providers simultaneamente em ordem.
     */
    public function boot(): void
    {
        if ($this->booted) {
            return; // Impede duplo boot acidental
        }

        foreach ($this->loadedProviders as $provider) {
            $provider->boot();
        }

        $this->booted = true;
    }

    /**
     * Retorna o Caminho Base da Applicação (Onde mora o vendor, core, public...)
     */
    public function basePath(): string
    {
        return $this->basePath;
    }
}
