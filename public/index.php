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
| A aplicação, sendo baseada em Request => Response passará
| as informações enviadas pelo navegador para o Kernel (Middlewares/Rotas).
*/

$request = \Core\Http\Request::capture();
$kernel = new \Core\Http\Kernel($app->get(\Core\Routing\Router::class));

$response = $kernel->handle($request);

/*
|--------------------------------------------------------------------------
| Entregue a Resposta ao Cliente Final
|--------------------------------------------------------------------------
*/

$response->send();
