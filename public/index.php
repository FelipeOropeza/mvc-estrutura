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
| Trate e Direcione o Request (Com Suporte a Worker Mode - FrankenPHP)
|--------------------------------------------------------------------------
|
| A aplicação processa a requisição e devolve uma resposta. Se estivermos
| rodando no modo Worker do FrankenPHP, o loop manterá a aplicação viva.
$kernel = new \Core\Http\Kernel($app->get(\Core\Routing\Router::class));
$request = \Core\Http\Request::capture();
$response = $kernel->handle($request);

/*
|--------------------------------------------------------------------------
| Entregue a Resposta ao Cliente Final
|--------------------------------------------------------------------------
*/

$response->send();
