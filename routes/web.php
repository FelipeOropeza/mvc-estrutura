<?php

use App\Controllers\HomeController;

/** @var \Core\Routing\Router $router */

// ==========================================
// ROTAS DE APLICAÇÃO (WEB / HTML)
// ==========================================

$router->get('/', [HomeController::class , 'index']);

// Exemplo testando a nova estrutura de Middlewares
$router->get('/teste-middleware', [HomeController::class , 'testeMiddleware'])
    ->middleware(\App\Middleware\TesteMiddleware::class);
