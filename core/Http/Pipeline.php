<?php

declare(strict_types=1);

namespace Core\Http;

use Closure;
use Core\Http\Request;

class Pipeline
{
    /**
     * O objeto de Request que passará pelos middlewares
     * 
     * @var Request
     */
    protected Request $passable;

    /**
     * O array contendo as instâncias ou classes dos Middlewares
     * 
     * @var array
     */
    protected array $pipes = [];

    /**
     * Envia o objeto Request pelo Pipeline
     * 
     * @param Request $passable
     * @return self
     */
    public function send(Request $passable): self
    {
        $this->passable = $passable;

        return $this;
    }

    /**
     * Define o array de Middlewares a serem executados
     * 
     * @param array $pipes
     * @return self
     */
    public function through(array $pipes): self
    {
        $this->pipes = $pipes;

        return $this;
    }

    /**
     * Executa o Pipeline em cascata até a função de destino (geralmente o Controller)
     * 
     * @param Closure $destination
     * @return mixed
     */
    public function then(Closure $destination): mixed
    {
        // Cria a cadeia de Closure inversa ("cebola")
        // Exemplo: o Middleware 1 chama o 2, que chama o Controller
        $pipeline = array_reduce(
            array_reverse($this->pipes),
            $this->carry(),
            $destination
        );

        // Dispara a viagem
        return $pipeline($this->passable);
    }

    /**
     * Prepara a função de callback (Closure) que embrulha o Middleware.
     * 
     * @return Closure
     */
    protected function carry(): Closure
    {
        return function ($stack, $pipe) {
            return function ($passable) use ($stack, $pipe) {
                // Suporte a Middleware com parãmetros via string (Ex: 'role:admin,editor')
                $params = [];
                if (is_string($pipe) && str_contains($pipe, ':')) {
                    [$pipe, $paramString] = explode(':', $pipe, 2);
                    $params = explode(',', $paramString);
                }

                // Instancia o middleware via Container
                if (is_string($pipe) && class_exists($pipe)) {
                    $pipe = \Core\Support\Container::getInstance()->get($pipe);
                }

                // Verifica se possui o método handle
                if (method_exists($pipe, 'handle')) {
                    // Passa Request, Next (stack) e os parâmetros extras desempacotados
                    return $pipe->handle($passable, $stack, ...$params);
                }

                return $stack($passable);
            };
        };
    }
}
