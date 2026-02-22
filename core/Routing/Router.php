<?php

namespace Core\Routing;

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

    public function put(string $uri, array |callable $action): void
    {
        $this->register('PUT', $uri, $action);
    }

    public function delete(string $uri, array |callable $action): void
    {
        $this->register('DELETE', $uri, $action);
    }

    protected function register(string $method, string $uri, array |callable $action): void
    {
        // Converte a URI que tem parâmetros como {id} para um padrão de Regex
        $uriPattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<\1>[a-zA-Z0-9_-]+)', $uri);
        // Escapa as barras e garante início e fim exatos
        $uriPattern = '#^' . str_replace('/', '\/', $uriPattern) . '$#';

        $this->routes[$method][$uriPattern] = $action;
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

        // Procura se alguma rota registrada casa com a URL usando Regex
        $matchedRoute = null;
        $matchedAction = null;
        $params = [];

        if (isset($this->routes[$method])) {
            foreach ($this->routes[$method] as $pattern => $action) {
                if (preg_match($pattern, $uri, $matches)) {
                    $matchedRoute = $pattern;
                    $matchedAction = $action;
                    // Filtra apenas os parametros nomeados (removendo os index numéricos do preg_match)
                    foreach ($matches as $key => $value) {
                        if (is_string($key)) {
                            $params[$key] = $value;
                        }
                    }
                    break;
                }
            }
        }

        if ($matchedAction) {
            if (is_callable($matchedAction)) {
                call_user_func_array($matchedAction, array_values($params));
                return;
            }

            if (is_array($matchedAction)) {
                [$controller, $methodName] = $matchedAction;

                // --- INÍCIO DA INJEÇÃO DE DEPENDÊNCIA ---
                // Verifica o construtor do Controller
                $reflector = new \ReflectionClass($controller);

                $constructorArgs = [];
                if ($constructor = $reflector->getConstructor()) {
                    foreach ($constructor->getParameters() as $param) {
                        $paramType = $param->getType();
                        // Se o construtor pedir uma classe, instanciamos ela pra ele (Dependency Injection)
                        if ($paramType && !$paramType->isBuiltin()) {
                            $className = $paramType->getName();
                            $constructorArgs[] = new $className();
                        }
                        else {
                            $constructorArgs[] = null; // Falta de DI avançada baseada em Request
                        }
                    }
                }

                $controllerInstance = $reflector->newInstanceArgs($constructorArgs);

                if (method_exists($controllerInstance, $methodName)) {
                    // Prepara os argumentos do método ($id, etc) na ordem que o controller pediu
                    $methodReflector = new \ReflectionMethod($controllerInstance, $methodName);
                    $methodArgs = [];

                    foreach ($methodReflector->getParameters() as $param) {
                        $paramName = $param->getName();
                        // Se a URL passou o parâmetro (ex: {id}), usamos ele
                        if (array_key_exists($paramName, $params)) {
                            $methodArgs[] = $params[$paramName];
                        }
                        // Se o método pedir uma Request global
                        else if ($param->getType() && $param->getType()->getName() === \Core\Http\Request::class) {
                            $methodArgs[] = request();
                        }
                        else {
                            $methodArgs[] = null;
                        }
                    }

                    // Tcharan! Chama o controlador com tudo que ele precisa!
                    $methodReflector->invokeArgs($controllerInstance, $methodArgs);
                    return;
                }
            }
        }

        // 404 handling simples
        http_response_code(404);
        echo "404 - Rota não encontrada: $uri";
    }
}
