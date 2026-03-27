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

$running = true;
$nbRequests = 0;

while ($running) {
    // Escuta e trata requisições no modo Worker do FrankenPHP (Alta Performance)
    if (isset($_SERVER['FRANKENPHP_WORKER']) && function_exists('frankenphp_handle_request')) {
        $running = call_user_func('frankenphp_handle_request', function () use ($kernel) {
            $request = \Core\Http\Request::capture();
            $response = $kernel->handle($request);
            $response->send();
        });

        // Evita memory leaks reciclando o worker após 500 requisições
        if ($nbRequests++ >= 500) {
            exit;
        }
    } else {
        // Servidor PHP Comum (PHP-FPM, Apache, ou servido local)
        $request = \Core\Http\Request::capture();
        $response = $kernel->handle($request);
        $response->send();
        $running = false; // Só processa uma vez e finaliza
    }
}
