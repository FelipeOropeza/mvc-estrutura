<?php

declare(strict_types=1);

namespace Core\Support;

use Exception;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;

class Container
{
    private static ?self $instance = null;
    private array $bindings = [];
    private array $instances = [];

    public function __construct()
    {
        self::$instance = $this;
    }

    /**
     * Retorna a instância global do Container (Singleton)
     * 
     * @return static
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Define a instância global do Container
     */
    public static function setInstance(self $container): void
    {
        self::$instance = $container;
    }

    /**
     * Registra um bind no container (A interface aponta para a implementação concreta)
     */
    public function bind(string $abstract, callable|string $concrete = null, bool $shared = false): void
    {
        if ($concrete === null) {
            $concrete = $abstract;
        }

        $this->bindings[$abstract] = compact('concrete', 'shared');
    }

    /**
     * Registra um singleton (instância única)
     */
    public function singleton(string $abstract, callable|string $concrete = null): void
    {
        $this->bind($abstract, $concrete, true);
    }

    /**
     * Registra uma instância já pronta no container
     */
    public function instance(string $abstract, mixed $instance): void
    {
        $this->instances[$abstract] = $instance;
    }

    /**
     * Verifica se o container possui um registro ou instância pronta para a classe informada.
     */
    public function has(string $abstract): bool
    {
        return isset($this->bindings[$abstract]) || isset($this->instances[$abstract]);
    }

    /**
     * Resolve/Busca a classe mapeada pelo Container com base nas dependências
     */
    public function get(string $abstract): mixed
    {
        // Se ela já foi instanciada como singleton, a retorna em vez de construir nova
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        $concrete = $this->bindings[$abstract]['concrete'] ?? $abstract;
        $shared = $this->bindings[$abstract]['shared'] ?? false;

        // Se for um callback, executa o factory
        if ($concrete instanceof \Closure) {
            $object = $concrete($this);
        } else {
            // Se for string/nome da classe, resolve construindo com Reflection
            $object = $this->build($concrete);
        }

        // Se marcou que é pra manter a instancia partilhada (singleton global), salvamos o objeto pronto
        if ($shared) {
            $this->instances[$abstract] = $object;
        }

        return $object;
    }

    /**
     * Constrói e injeta dinamicamente as classes necessárias do construtor
     */
    public function build(string $concrete): mixed
    {
        try {
            $reflector = new ReflectionClass($concrete);
        } catch (Exception $e) {
            throw new Exception("A classe/interface [{$concrete}] não existe ou não pôde ser refletida.", 0, $e);
        }

        if (!$reflector->isInstantiable()) {
            throw new Exception("A classe [{$concrete}] não é instanciabilizável (Interface ou Abstract pura sem bind mapeado).");
        }

        $constructor = $reflector->getConstructor();

        // Se a classe não precisa do construtor, podemos simplesmente darmos um instanciamento limpo
        if ($constructor === null) {
            return new $concrete();
        }

        // Busca o que o construtor desta classe "pede" para ela funcionar... (Database? UserRepository?)
        $dependencies = $constructor->getParameters();

        // Vamos percorrer o construtor dela e fabricar dinamicamente os itens que ela pede também!
        $instances = $this->resolveDependencies($dependencies);

        return $reflector->newInstanceArgs($instances);
    }

    /**
     * Resolve todas as dependências requeridas por um método (construtor ou actions)
     */
    private function resolveDependencies(array $dependencies): array
    {
        $resolved = [];

        foreach ($dependencies as $dependency) {
            $type = $dependency->getType();

            // Se for uma classe/interface explicita, entramos num loop pra buscar ELA no container (Inception!)
            if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                $resolved[] = $this->get($type->getName());
            } elseif ($dependency->isDefaultValueAvailable()) {
                // Se o programador forneceu um "null" padrão na declaração
                $resolved[] = $dependency->getDefaultValue();
            } else {
                throw new Exception("Não foi possível resolver o parâmetro [{$dependency->name}] sem um valor fornecido.");
            }
        }

        return $resolved;
    }

    /**
     * Injeta magicamente os parametros de um Closure ou array tipo [Controller::class, 'metodo']
     */
    public function call(array|callable $action, array $parameters = []): mixed
    {
        // Se a rota for só uma Closure simples (Ex: Route::get('/foo', function(){}))
        if (is_callable($action) && !is_array($action)) {
            // Futuramente poderíamos injetar a respsota de \Closure com `ReflectionFunction`. Para iniciar simples:
            return call_user_func_array($action, array_values($parameters));
        }

        // O Padrão normal do Micro Framework: Array com a Action do Controller
        if (is_array($action)) {
            [$controller, $methodName] = $action;

            // Busca o Controller do Container (que instanciará automaticamente os repositórios/bancos pelo construct dele)
            $controllerInstance = is_string($controller) ? $this->get($controller) : $controller;

            if (method_exists($controllerInstance, $methodName)) {
                $reflector = new ReflectionMethod($controllerInstance, $methodName);
                $dependencies = $reflector->getParameters();

                // Mescla os parametros url ($id, $slug) com a Injeção dos Typed Hints da function ($request, etc)
                $methodArgs = $this->resolveMethodDependencies($dependencies, $parameters);

                return $reflector->invokeArgs($controllerInstance, $methodArgs);
            } else {
                throw new Exception("O método [{$methodName}] não foi encontrado na classe [" . get_class($controllerInstance) . "].");
            }
        }

        return null;
    }

    /**
     * Resolve dependências misturando a URL da Rota e os Tipos Dinâmicos da Funcão do Controller
     */
    private function resolveMethodDependencies(array $dependencies, array $parameters): array
    {
        $resolved = [];

        foreach ($dependencies as $dependency) {
            $name = $dependency->getName();
            $type = $dependency->getType();

            // 1) Se a URl da rota mandou esse param ({id} na rota vindo pro $id na funcao)
            if (array_key_exists($name, $parameters)) {
                $resolved[] = $parameters[$name];
            }
            // 2) Se o programador tipou uma classe! (Ex: injetou um Core\Http\Request $req na function)
            elseif ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                $resolved[] = $this->get($type->getName()); // Resolve essa classe globalmente com Autowiring!
            }
            // 3) Tem valor padrão = null ou algo?
            elseif ($dependency->isDefaultValueAvailable()) {
                $resolved[] = $dependency->getDefaultValue();
            }
            // 4) Paciência, assume nulo (ou dar erro dependendo do grau de segurança)
            else {
                $resolved[] = null;
            }
        }

        return $resolved;
    }
}
