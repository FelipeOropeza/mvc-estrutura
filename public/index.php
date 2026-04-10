<?php

/**
 * MVC Base Project - Micro Framework
 * Um framework PHP simplificado e performático de arquitetura moderna (Stateless).
 */

require_once __DIR__ . '/../vendor/autoload.php';

/*
|--------------------------------------------------------------------------
| Inicie a Aplicação e o "Motor" (Container + Providers)
|--------------------------------------------------------------------------
|
| Importamos o script de configuração global da aplicação. 
| Lá é onde o ambiente, a injeção de dependências e os provedores são lidos.
*/

$app = require_once __DIR__ . '/../bootstrap/app.php';

/*
|--------------------------------------------------------------------------
| Trate e Direcione o Request
|--------------------------------------------------------------------------
|
| A aplicação processa a requisição e devolve uma resposta. Se estivermos
| rodando sob o Docker (FrankenPHP Worker), mantemos a fita rodando rápida!
*/

$kernel = new \Core\Http\Kernel($app->get(\Core\Routing\Router::class));

$handler = function () use ($kernel) {
    $request = \Core\Http\Request::capture();
    $response = $kernel->handle($request);
    $response->send();
};

if (isset($_SERVER['FRANKENPHP_WORKER']) && function_exists('frankenphp_handle_request')) {
    // Modo Worker do FrankenPHP (Alta Performance)
    $maxRequests = (int)($_SERVER['MAX_REQUESTS'] ?? 500);
    $running = true;
    $nbRequests = 0;

    while ($running) {
        $running = call_user_func('frankenphp_handle_request', $handler);

        // Limpa o lixo de memória para evitar leaks durante execuções prolongadas
        gc_collect_cycles();

        // Recicla o worker após o número máximo de requisições
        if ($maxRequests && ++$nbRequests >= $maxRequests) {
            $running = false;
        }
    }
} else {
    // Servidor PHP Comum (PHP-FPM, Apache, CLI Server)
    $handler();
}
