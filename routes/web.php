<?php

use App\Controllers\HomeController;
use Core\Routing\Route;

/** @var \Core\Routing\Router $router */

// ==========================================
// ROTAS DE APLICAÇÃO (WEB / HTML)
// ==========================================

// A Rota /home não está mais aqui, ela foi movida para atributos no HomeController!

Route::get('/', [HomeController::class, 'index']);
