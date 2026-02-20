<?php

use Core\Router;

require_once __DIR__ . '/../vendor/autoload.php';

// Inicia a sessão para suportar Flash Data (Erros de Validação e Inputs antigos)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Move as mensagens flash da sessão para uma variável global temporária 
// para estarem disponíveis apenas durante este request, e as apaga da sessão real.
$GLOBALS['flash_errors'] = $_SESSION['_flash_errors'] ?? [];
$GLOBALS['flash_old'] = $_SESSION['_flash_old'] ?? [];
unset($_SESSION['_flash_errors'], $_SESSION['_flash_old']);
// require_once __DIR__ . '/../config/config.php';

$router = new Router();

// Carrega as rotas da aplicação
require_once __DIR__ . '/../routes/web.php';

// Dispara a rota correspondente
$router->dispatch();
