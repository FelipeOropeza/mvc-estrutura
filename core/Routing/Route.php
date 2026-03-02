<?php

declare(strict_types=1);

namespace Core\Routing;

/**
 * Class Route
 * 
 * Facade estática para o Router. Permite definir rotas usando Route::get()
 * de forma similar ao framework Laravel.
 * 
 * @method static \Core\Routing\Router get(string $uri, array|\Closure|callable $action)
 * @method static \Core\Routing\Router post(string $uri, array|\Closure|callable $action)
 * @method static \Core\Routing\Router put(string $uri, array|\Closure|callable $action)
 * @method static \Core\Routing\Router delete(string $uri, array|\Closure|callable $action)
 * @method static \Core\Routing\Router patch(string $uri, array|\Closure|callable $action)
 * @method static void group(array $attributes, \Closure $callback)
 * @method static \Core\Routing\Router middleware(string|array $middleware)
 */
class Route
{
    /**
     * Encaminha chamadas estáticas para a instância ativa do Router.
     * 
     * @param string $method
     * @param array $arguments
     * @return mixed
     * @throws \Exception
     */
    public static function __callStatic(string $method, array $arguments)
    {
        $instance = Router::getInstance();

        if (!$instance) {
            throw new \Exception("Nenhuma instância do Router foi encontrada. Verifique se o RoutingServiceProvider está registrado.");
        }

        if (!method_exists($instance, $method)) {
            throw new \Exception("O método '{$method}' não existe no Router.");
        }

        return $instance->$method(...$arguments);
    }
}
