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
    protected function getGlobalMiddlewares(): array
    {
        $container = \Core\Support\Container::getInstance();
        $config = $container->has('config') ? $container->get('config') : [];

        // Puxa do arquivo config/middleware.php se existir, senão usa o padrão
        if (isset($config['middleware']['global'])) {
            return $config['middleware']['global'];
        }

        // Fallback padrão se não tiver config
        return [
            \Core\Http\Middleware\StartSession::class,
        ];
    }

    /**
     * Lida com uma requisição HTTP e retorna uma Resposta encapsulada (Pipeline pattern).
     */
    public function handle(Request $request): Response
    {
        try {
            // Em vez de rodarmos globais com `Pipeline` e depois o do Router com Pipeline,
            // podemos apenas enviar a Request para o Router. 
            // Opcionalmente, um Pipeline global em volta do Roteador também funciona.

            $pipeline = new Pipeline();
            $response = $pipeline
                ->send($request)
                ->through($this->getGlobalMiddlewares())
                ->then(fn($req) => $this->router->dispatch($req));

            return $response;
        } catch (\Throwable $e) {
            return $this->renderException($request, $e);
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

        return $handler->renderException($e);
    }
}
