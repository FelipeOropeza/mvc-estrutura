<?php

namespace App\Middleware;

use Closure;
use Core\Contracts\MiddlewareInterface;
use Core\Http\Request;

class AuthMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, Closure $next)
    {
        // === LÓGICA ANTES DA REQUISIÇÃO (DO CONTROLLER) ===
        // Exemplo de como validar um token ou permissão:
        // if (!$request->get('api_token')) {
        //     response()->json(['error' => 'Acesso Negado. Require um API Token'], 401);
        //     exit;
        // }
        // Se desejar injetar algo no controller: $request->attributes['usuario'] = $user;

        // Segue o fluxo adiante (Passa pro Controller ou proximo Middleware)
        $response = $next($request);

        // === LÓGICA DEPOIS DA REQUISIÇÃO (DA RESPOSTA GERADA) ===
        // Aqui você pode logar, limpar headers, etc.

        return $response;
    }
}
