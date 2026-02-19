<?php

use App\Controllers\HomeController;

/** @var \Core\Router $router */

// Defina suas rotas aqui

// Rota principal (Home)
$router->get('/', [HomeController::class , 'index']);

// Exemplo de outra rota (descomente para testar)
// $router->get('/sobre', function() {
//     echo "Página Sobre";
// });
