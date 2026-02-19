<?php

namespace Core;

class Router
{
    protected array $routes = [];

    public function get(string $uri, array |callable $action): void
    {
        $this->register('GET', $uri, $action);
    }

    public function post(string $uri, array |callable $action): void
    {
        $this->register('POST', $uri, $action);
    }

    protected function register(string $method, string $uri, array |callable $action): void
    {
        $this->routes[$method][$uri] = $action;
    }

    public function dispatch(): void
    {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $method = $_SERVER['REQUEST_METHOD'];

        // Tenta detectar se estamos rodando em um subdiretório
        $scriptName = dirname($_SERVER['SCRIPT_NAME']);

        // Se o scriptName não for apenas '/' (root), removemos ele da URI
        if ($scriptName !== '/' && strpos($uri, $scriptName) === 0) {
            $uri = substr($uri, strlen($scriptName));
        }

        // Garante que a URI comece com '/' e não termine com '/' (exceto se for apenas '/')
        $uri = '/' . trim($uri, '/');

        if (isset($this->routes[$method][$uri])) {
            $action = $this->routes[$method][$uri];

            if (is_callable($action)) {
                call_user_func($action);
                return;
            }

            if (is_array($action)) {
                [$controller, $method] = $action;
                // Instancia o controller e chama o método
                // Nota: Em um framework real, usaria Dependency Injection aqui
                $controllerInstance = new $controller();
                if (method_exists($controllerInstance, $method)) {
                    $controllerInstance->$method();
                    return;
                }
            }
        }

        // 404 handling simples
        http_response_code(404);
        echo "404 - Rota não encontrada: $uri";
    }
}
