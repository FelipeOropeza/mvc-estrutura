<?php

use Core\Routing\Router;
use Core\Exceptions\Handler;

require_once __DIR__ . '/../vendor/autoload.php';

// Registra o tratador global de exceções
$exceptionHandler = new Handler();
$exceptionHandler->register();

// Tenta carregar variáveis de ambiente
if (class_exists(\Dotenv\Dotenv::class) && file_exists(__DIR__ . '/../.env')) {
    $dotenv = \Dotenv\Dotenv::createUnsafeImmutable(__DIR__ . '/../');
    $dotenv->safeLoad();
}

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

// move o container boot para a parte superior 
$container = \Core\Support\Container::getInstance();

// Inicializamos o componente Central de Roteamento
$router = new Router();
$container->instance(Router::class, $router);

// Carrega as rotas da aplicação
require_once __DIR__ . '/../routes/web.php';

// Dispara a Rota usando a nova Arquitetura PSR-15 (Request -> Kernel -> Response)
// Tudo vira objeto (Sem globais sujas como $_GET/$_POST voando)
$request = \Core\Http\Request::capture();
$kernel = new \Core\Http\Kernel($router); // Ou $container->get(Kernel::class)

$response = $kernel->handle($request);

// O Laravel / FrankenPHP manda a Resposta final para o cliente aqui
$response->send();
