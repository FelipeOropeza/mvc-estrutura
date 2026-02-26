<?php

/**
 * MVC Base Project - Local Server Router
 * 
 * Este arquivo replica a funcionalidade do mod_rewrite do Apache para o Servidor
 * embutido do PHP (php -S localhost:8000 server.php), permitindo que rotemos
 * todas as requisições para public/index.php.
 */

$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)
);

// Este arquivo permite rodar a aplicação via 'php -S localhost:8000 server.php' na RAIZ 
// emulandando o public_html, barrando acesso a pastas internas (core, app, etc).

if ($uri !== '/' && file_exists(__DIR__ . '/public' . $uri)) {
    return false; // Deixa o PHP servir o arquivo físico que está em /public
}

// Redireciona tudo invisivelmente para o ponto central protegido.
require_once __DIR__ . '/public/index.php';
