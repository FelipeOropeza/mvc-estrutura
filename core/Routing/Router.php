<?php

declare(strict_types=1);

namespace Core\Routing;

use Closure;

class Router
{
    protected static ?self $instance = null;
    protected array $routes = [];
    protected array $namedRoutes = [];
    protected array $groupMiddlewares = []; // Para armazenar provisoriamente (se tivermos grupos no futuro)

    /**
     * Retorna a nova rota/ação associada para podermos encadear métodos nela.
     * Retornaremos o próprio Router e controlaremos o "último adicionado".
     */
    protected ?string $lastAddedMethod = null;
    protected ?string $lastAddedPattern = null;
    protected ?string $lastAddedUri = null;

    public function __construct()
    {
        self::$instance = $this;
    }

    public static function getInstance(): ?self
    {
        return self::$instance;
    }

    public function get(string $uri, array|Closure|callable $action): self
    {
        return $this->register('GET', $uri, $action);
    }

    public function post(string $uri, array|Closure|callable $action): self
    {
        return $this->register('POST', $uri, $action);
    }

    public function put(string $uri, array|Closure|callable $action): self
    {
        return $this->register('PUT', $uri, $action);
    }

    public function delete(string $uri, array|Closure|callable $action): self
    {
        return $this->register('DELETE', $uri, $action);
    }

    protected function register(string $method, string $uri, array|Closure|callable $action): self
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
        $this->lastAddedUri = $uri;

        return $this;
    }

    /**
     * Nomear a última rota adicionada.
     */
    public function name(string $name): self
    {
        if ($this->lastAddedUri !== null) {
            $this->namedRoutes[$name] = $this->lastAddedUri;
        }

        return $this;
    }

    /**
     * Gera uma URL completa para uma rota nomeada com base nos parâmetros
     */
    public function generateUrl(string $name, array $params = []): string
    {
        if (!isset($this->namedRoutes[$name])) {
            throw new \Exception("A rota com o nome '{$name}' não foi encontrada.");
        }

        $uri = $this->namedRoutes[$name];
        $queryParams = [];

        // Substitui os parâmetros dinâmicos na URI (ex: {id} por 3)
        foreach ($params as $key => $value) {
            $placeholder = '{' . $key . '}';
            if (strpos($uri, $placeholder) !== false) {
                $uri = str_replace($placeholder, (string)$value, $uri);
            } else {
                // Se o parâmetro não faz parte da URI, guardamos para ser uma query string
                $queryParams[$key] = $value;
            }
        }

        // Se sobraram parâmetros extras, adiciona como query string
        if (!empty($queryParams)) {
            $uri .= '?' . http_build_query($queryParams);
        }

        // Tenta detectar se estamos rodando em um subdiretório
        $scriptName = dirname($_SERVER['SCRIPT_NAME'] ?? '');
        if ($scriptName === '/' || $scriptName === '\\') {
            $scriptName = '';
        }

        return $scriptName . $uri;
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

    public function dispatch(\Core\Http\Request $request): \Core\Http\Response
    {
        $uri = parse_url($request->server['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $method = $request->server['REQUEST_METHOD'] ?? 'GET';

        // Tenta detectar se estamos rodando em um subdiretório
        $scriptName = dirname($request->server['SCRIPT_NAME'] ?? '');

        // Se o scriptName não for apenas '/' (root), removemos ele da URI
        if ($scriptName !== '/' && strpos((string) $uri, (string) $scriptName) === 0) {
            $uri = substr((string) $uri, strlen((string) $scriptName));
        }

        // Garante que a URI comece com '/' e não termine com '/' (exceto se for apenas '/')
        $uri = '/' . trim((string) $uri, '/');

        // Lógica Global de Redirecionamento da Rota Raiz (Lida da Configuração)
        if ($uri === '/') {
            $container = \Core\Support\Container::getInstance();
            $config = $container->has('config') ? $container->get('config') : require __DIR__ . '/../../config/app.php';
            $defaultRoute = $config['app']['default_route'] ?? '/';

            if ($defaultRoute !== '/') {
                return \Core\Http\Response::makeRedirect($defaultRoute, 302);
            }
        }

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
                $container = \Core\Support\Container::getInstance();

                // Se a ação já for um callable simples (Closure)
                if (is_callable($action) && !is_array($action)) {
                    return call_user_func_array($action, array_values($params));
                }

                // O Action Controller com o Container da PSR-11 fazendo a Magica de Autowiring!
                return $container->call($action, $params);
            }; // Fim da destination / Action Controller


            // A Requisição `$request` já foi passada instanciada pelo Kernel e Injetada no Dispatch
            // Apenas garantimos de cadastrá-la no Container pra todo o framework poder Injetá-la
            \Core\Support\Container::getInstance()->instance(\Core\Http\Request::class, $request);

            // Criamos e executamos a Pipeline de Middlewares injetando no fim o Destination (Action)
            $pipeline = new \Core\Http\Pipeline();
            $result = $pipeline
                ->send($request)
                ->through($routeMiddlewares)
                ->then($destination);

            // Garante que o retorno seja sempre um objeto Core\Http\Response
            if ($result instanceof \Core\Http\Response) {
                return $result;
            }

            if (is_array($result) || is_object($result)) {
                return \Core\Http\Response::makeJson($result);
            }

            return new \Core\Http\Response((string) $result);
        }

        // 404 handling simples
        return new \Core\Http\Response("404 - Rota não encontrada: $uri", 404);
    }
}
