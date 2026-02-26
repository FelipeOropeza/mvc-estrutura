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
        // Aqui conectamos ao global Handler que você já tem
        $handler = new \Core\Exceptions\Handler();

        // Vamos capturar a saída usando output buffering para transformar em Response
        ob_start();
        $handler->handleException($e);
        $content = ob_get_clean();

        // Obtém o código real de erro HTTP (ex: 404, 500) que o handler configurou com http_response_code
        // Note: Se o Exception handler chamar exit() ainda vai matar, 
        // mas idealmente ajustaremos o Handler pra não chamar exit no futuro.

        return new Response((string) $content, http_response_code() ?: 500);
    }
}
