<?php

declare(strict_types=1);

namespace Core\Http;

use Core\Routing\Router;

class Kernel
{
    protected Router $router;

    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    /**
     * Retorna os Middlewares globais lendo das configurações.
     */
    protected function getGlobalMiddlewares(Request $request): array
    {
        $container = \Core\Support\Container::getInstance();
        $config = $container->has('config') ? $container->get('config') : [];

        $middlewares = $config['middleware']['global'] ?? [
            \Core\Http\Middleware\StartSession::class,
        ];

        // Se for API, removemos middlewares de estado (como Sessão) para garantir Stateless
        if ($request->isApi()) {
            $middlewares = array_filter($middlewares, function ($middleware) {
                return !str_contains((string)$middleware, 'StartSession');
            });
        }

        return array_values($middlewares);
    }

    /**
     * Lida com uma requisição HTTP e retorna uma Resposta encapsulada (Pipeline pattern).
     */
    public function handle(Request $request): Response
    {
        try {
            // Garante que a injeção de dependências e os helpers recebam a requisição MAIS RECENTE rotativa (Worker Mode PHP)
            \Core\Support\Container::getInstance()->instance(Request::class, $request);

            // Em vez de rodarmos globais com `Pipeline` e depois o do Router com Pipeline,
            // podemos apenas enviar a Request para o Router. 
            // Opcionalmente, um Pipeline global em volta do Roteador também funciona.

            $pipeline = new Pipeline();
            $response = $pipeline
                ->send($request)
                ->through($this->getGlobalMiddlewares($request))
                ->then(fn($req) => $this->router->dispatch($req));

            // Limpa estados residuais para a próxima requisição (FrankenPHP/Worker)
            $this->terminate($request);

            return $response;
        } catch (\Throwable $e) {
            return $this->renderException($request, $e);
        }
    }

    /**
     * Finaliza a requisição limpando caches estáticos e estados de Singletons.
     */
    public function terminate(Request $request): void
    {
        // Reseta cache de discos do Storage
        \Core\Storage\StorageManager::reset();

        // Se houver engine de view, reseta estados de sections/layouts
        if (\Core\Support\Container::getInstance()->has(\Core\View\EngineInterface::class)) {
            $engine = app(\Core\View\EngineInterface::class);
            if (method_exists($engine, 'resetState')) {
                $engine->resetState();
            }
        }
    }

    /**
     * Trata erros ocorridos DENTRO da pipeline (Kernel), permitindo retornar 
     * respostas formadas em vez de "quebrar" fatalmente (necessário para FrankenPHP).
     */
    protected function renderException(Request $request, \Throwable $e): Response
    {
        // Aqui conectamos ao global Handler para extrair um Objeto Response pronto
        $handler = new \Core\Exceptions\Handler();

        return $handler->renderException($e, $request);
    }
}
