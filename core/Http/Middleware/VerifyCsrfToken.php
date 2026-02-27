<?php

declare(strict_types=1);

namespace Core\Http\Middleware;

use Closure;
use Core\Http\Request;
use Core\Http\Response;

class VerifyCsrfToken
{
    /**
     * Uris que devem ser ignoradas na verificação.
     */
    protected array $except = [
        // '/api/*'
    ];

    public function handle(Request $request, Closure $next): Response
    {
        // Métodos de leitura estão imunes
        if (in_array($request->server['REQUEST_METHOD'] ?? 'GET', ['HEAD', 'GET', 'OPTIONS'])) {
            return $next($request);
        }

        // Verifica a token se for um POST/PUT/DELETE
        $session = $request->session();

        $token = $request->post['_token'] ?? $request->server['HTTP_X_CSRF_TOKEN'] ?? null;

        if (!$session || !$token || !hash_equals($session->token(), $token)) {
            // Em APIs pode ser 403 JSON, mas vamos simplificar com exceptions
            throw new \Exception("Ação não autorizada. (Descompasso no Token CSRF)", 403);
        }

        return $next($request);
    }
}
