<?php

declare(strict_types=1);

namespace App\Middleware;

use Core\Http\Request;
use Core\Http\Response;

class AuthMiddleware
{
    /**
     * Verifica se o usuário está logado.
     * Caso contrário, redireciona para a página de login.
     * 
     * @param Request $request
     * @param callable $next
     * @return Response
     */
    public function handle(Request $request, callable $next): Response
    {
        // Verifica se existe o usuário na sessão
        if (!session()->has('user')) {
            
            // Se for uma requisição HTMX, forçamos o redirecionamento da página inteira
            if ($request->isHtmx()) {
                return response()->hxRedirect('/login');
            }

            // Se for AJAX comum (JSON/Fetch), retornamos erro 401
            if ($request->isAjax()) {
                return response()->json(['error' => 'Sessão expirada.'], 401);
            }

            // Para navegação comum, redireciona para login
            return Response::makeRedirect('/login');
        }

        return $next($request);
    }
}
