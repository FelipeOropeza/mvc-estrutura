<?php

namespace Core\Routing;

class Router
{
    protected array $routes = [];
    protected array $groupMiddlewares = []; // Para armazenar provisoriamente (se tivermos grupos no futuro)

    /**
     * Retorna a nova rota/ação associada para podermos encadear métodos nela.
     * Retornaremos o próprio Router e controlaremos o "último adicionado".
     */
    protected ?string $lastAddedMethod = null;
    protected ?string $lastAddedPattern = null;

    public function get(string $uri, array |callable $action): self
    {
        return $this->register('GET', $uri, $action);
    }

    public function post(string $uri, array |callable $action): self
    {
        return $this->register('POST', $uri, $action);
    }

    public function put(string $uri, array |callable $action): self
    {
        return $this->register('PUT', $uri, $action);
    }

    public function delete(string $uri, array |callable $action): self
    {
        return $this->register('DELETE', $uri, $action);
    }

    protected function register(string $method, string $uri, array |callable $action): self
    {
        // Converte a URI que tem parâmetros como {id} para um padrão de Regex
        $uriPattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<\1>[a-zA-Z0-9_-]+)', $uri);
        // Escapa as barras e garante início e fim exatos
        $uriPattern = '#^' . str_replace('/', '\/', $uriPattern) . '$#';

        $this->routes[$method][$uriPattern] = [
            'action' => $action,
            'middlewares' => [] // Array vazio para receber os pipes depois
        ];

        // Guardamos as configs da última rota adicionada pra podermos encadear chamadas a ela
        $this->lastAddedMethod = $method;
        $this->lastAddedPattern = $uriPattern;

        return $this;
    }

    /**
     * Encadear e registrar um Web Middleware nesta rota.
     * Exemplo: Route::get('/admin')->middleware(AuthMiddleware::class);
     * 
     * @param string|array $middleware array de classes de middleware ou apenas uma
     */
    public function middleware(string|array $middleware): self
    {
        if ($this->lastAddedMethod && $this->lastAddedPattern) {
            $middlewares = is_array($middleware) ? $middleware : [$middleware];

            // Adiciona na última rota registrada
            $this->routes[$this->lastAddedMethod][$this->lastAddedPattern]['middlewares'] = array_merge(
                $this->routes[$this->lastAddedMethod][$this->lastAddedPattern]['middlewares'],
                $middlewares
            );
        }

        return $this;
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
        $matchedRouteInfos = null;
        $params = [];

        if (isset($this->routes[$method])) {
            foreach ($this->routes[$method] as $pattern => $info) {
                if (preg_match($pattern, $uri, $matches)) {
                    $matchedRouteInfos = $info;

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

        if ($matchedRouteInfos) {
            $action = $matchedRouteInfos['action'];
            $routeMiddlewares = $matchedRouteInfos['middlewares'];

            // Vamos construir a destinação final (O Action/Controller sendo invocado)
            // Esse é o centro absoluto da cebola
            $destination = function (\Core\Http\Request $request) use ($action, $params) {
                if (is_callable($action)) {
                    return call_user_func_array($action, array_values($params));
                }

                if (is_array($action)) {
                    [$controller, $methodName] = $action;

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
                                $constructorArgs[] = null;
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

                            if (array_key_exists($paramName, $params)) {
                                $methodArgs[] = $params[$paramName];
                            }
                            else if ($param->getType() && $param->getType()->getName() === \Core\Http\Request::class) {
                                // Injetamos a Request se ele a solicitou e ela entrou como parametro no closure!
                                $methodArgs[] = $request;
                            }
                            else {
                                $methodArgs[] = null;
                            }
                        }

                        // Chama o controller e retorna os dados
                        return $methodReflector->invokeArgs($controllerInstance, $methodArgs);
                    }
                }
            }; // Fim da destination / Action Controller


            // Criamos o objeto global Request (que pode ser interceptado e modificado) 
            $request = request();

            // Criamos e executamos a Pipeline de Middlewares injetando no fim o Destination (Action)
            $pipeline = new \Core\Http\Pipeline();
            $pipeline
                ->send($request)
                ->through($routeMiddlewares)
                ->then($destination);

            return; // Terminou o dispatch da rota mapeada
        }

        // 404 handling simples
        http_response_code(404);
        echo "404 - Rota não encontrada: $uri";
    }
}
