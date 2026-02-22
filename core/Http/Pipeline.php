<?php

namespace Core\Http;

use Closure;
use Core\Http\Request;

class Pipeline
{
    /**
     * O objeto de Request que passará pelos middlewares
     */
    protected Request $passable;

    /**
     * O array contendo as instâncias ou classes dos Middlewares
     */
    protected array $pipes = [];

    /**
     * Envia o objeto Request pelo Pipeline
     */
    public function send(Request $passable): self
    {
        $this->passable = $passable;
        return $this;
    }

    /**
     * Define o array de Middlewares a serem executados
     */
    public function through(array $pipes): self
    {
        $this->pipes = $pipes;
        return $this;
    }

    /**
     * Executa o Pipeline em cascata até a função de destino (geralmente o Controller)
     */
    public function then(Closure $destination)
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
     */
    protected function carry(): Closure
    {
        return function ($stack, $pipe) {
            return function ($passable) use ($stack, $pipe) {
                    // Instancia o middleware caso ele seja passado como String (nome da classe)
                    if (is_string($pipe) && class_exists($pipe)) {
                        $pipe = new $pipe();
                    }

                    // Verifica se implementa nosso MiddlewareInterface ou possui o método handle
                    if (method_exists($pipe, 'handle')) {
                        return $pipe->handle($passable, $stack);
                    }

                    // Se o middleware for inválido, apenas continua pro próximo
                    return $stack($passable);
                }
                    ;
            };
    }
}
