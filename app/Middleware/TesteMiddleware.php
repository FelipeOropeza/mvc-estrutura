<?php

namespace App\Middleware;

use Closure;
use Core\Contracts\MiddlewareInterface;
use Core\Http\Request;

class TesteMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, Closure $next)
    {
        // === ANTES DA REQUISIÇÃO (DO CONTROLLER) ===
        // Aqui você faria checagens, ex:
        // if (!$request->hasHeader('Authorization')) { ... }

        $request->attributes['middleware_teste'] = 'Este valor foi injetado pelo Middleware!';

        // Manda o fluxo da requisição seguir adiante (para o próximo Middleware ou para o Controller!)
        $response = $next($request);

        // === DEPOIS DA REQUISIÇÃO ===
        // Você poderia interceptar a view já gerada aqui e logar ela no banco, etc.

        return $response;
    }
}
