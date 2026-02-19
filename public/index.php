<?php

use Core\Router;

require_once __DIR__ . '/../vendor/autoload.php';

// Carrega as configurações (se houver)
// require_once __DIR__ . '/../config/config.php';

$router = new Router();

// Carrega as rotas da aplicação
require_once __DIR__ . '/../routes/web.php';

// Dispara a rota correspondente
$router->dispatch();
