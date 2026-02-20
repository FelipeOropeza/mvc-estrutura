<?php

use App\Controllers\HomeController;

/** @var \Core\Router $router */

// ==========================================
// ROTAS DE APLICAÇÃO (WEB / HTML)
// ==========================================

$router->get('/', [HomeController::class , 'index']);
