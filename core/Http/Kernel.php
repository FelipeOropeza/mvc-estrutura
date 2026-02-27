<?php

declare(strict_types=1);

namespace Core\Http;

use Core\Routing\Router;

class Kernel
{
    protected Router $router;

    /**
     * Middlewares globais que rodam em toda requisição, antes de descobrir a rota.
     */
    protected array $globalMiddlewares = [
        \Core\Http\Middleware\StartSession::class,
        // \App\Http\Middlewares\HandleCors::class,
    ];

    public function __construct(Router $router)
    {
        $this->router = $router;
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
                ->through($this->globalMiddlewares)
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
