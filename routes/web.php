<?php

use App\Controllers\HomeController;

/** @var \Core\Routing\Router $router */

// ==========================================
// ROTAS DE APLICAÇÃO (WEB / HTML)
// ==========================================

$router->get('/home', [HomeController::class , 'index']);
