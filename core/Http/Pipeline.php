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
                // Instancia o middleware via Container para permitir Injeção de Dependências no construct!
                if (is_string($pipe) && class_exists($pipe)) {
                    $pipe = \Core\Support\Container::getInstance()->get($pipe);
                }

                // Verifica se implementa nosso MiddlewareInterface ou possui o método handle
                if (method_exists($pipe, 'handle')) {
                    return $pipe->handle($passable, $stack);
                }

                // Se o middleware for inválido, apenas continua pro próximo
                return $stack($passable);
            };
        };
    }
}
