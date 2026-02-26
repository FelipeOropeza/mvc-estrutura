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
// ==========================================
// 🚀 INICIALIZAÇÃO E ARQUITETURA DE SERVIÇOS
// ==========================================

// 1. Inicia o "App" fornecendo a base principal onde o framework e a loja moram
$app = new \Core\Foundation\Application(realpath(__DIR__ . '/../'));

// 2. Lê configurações e aciona todos os provedores na Prancheta (Register)
$app->registerConfiguredProviders();

// 3. Dá o Boot (Liga todo o sistema na ordem correta)
$app->boot();

// ==========================================
// 📡 CICLO DE VIDA DA REQUISIÇÃO (Stateless)
// ==========================================

// Request viaja pelo Kernel de Middlewares até o Controlador e volta como Resposta
$request = \Core\Http\Request::capture();

// O Router já foi automaticamente criado pelo RoutingServiceProvider
// Kernel agora pode inclusive ser magicizado (resolvido automaticamente mas p/ simplicidade criamos manual por enqnato)
$kernel = new \Core\Http\Kernel($app->get(Router::class));

$response = $kernel->handle($request);
$response->send();
