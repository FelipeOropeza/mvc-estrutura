<?php

namespace Core\Contracts;

use Closure;
use Core\Http\Request;

interface MiddlewareInterface
{
    /**
     * Intercepta a requisição antes de chegar ao Controller.
     * Deve retornar a execução do $next() para permitir que a requisição siga em frente.
     */
    public function handle(Request $request, Closure $next);
}
