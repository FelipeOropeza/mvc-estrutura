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

if (isset($_SERVER['FRANKENPHP_WORKER']) && function_exists('frankenphp_handle_request')) {
    // Loop de Alta-Performance pelo Docker
    $handler = static function () use ($kernel) {
        $request = \Core\Http\Request::capture();
        $response = $kernel->handle($request);
        $response->send();
    };
    // Ignora o aviso de erro da IDE chamada de funcão dinamicamente (Extensão C)
    call_user_func('frankenphp_handle_request', $handler);
} else {
    // Servidor normal (Apache, Nginx, ou FPM)
    $request = \Core\Http\Request::capture();
    $response = $kernel->handle($request);
    $response->send();
}
