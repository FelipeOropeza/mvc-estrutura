<?php

declare(strict_types=1);

namespace Core\Http\Middleware;

use Closure;
use Core\Http\Request;
use Core\Http\Response;

/**
 * Adiciona cabeçalhos de segurança essenciais para proteger contra XSS, 
 * Clickjacking e Sniffing de tipos de conteúdo.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Previne o site de ser emoldurado (evita Clickjacking)
        $response->setHeader('X-Frame-Options', 'SAMEORIGIN');

        // Previne o navegador de adivinhar o tipo de conteúdo (evita MIME Sniffing)
        $response->setHeader('X-Content-Type-Options', 'nosniff');

        // Ativa o filtro de XSS do navegador (legado, mas útil)
        $response->setHeader('X-XSS-Protection', '1; mode=block');

        // Política de Referência
        $response->setHeader('Referrer-Policy', 'no-referrer-when-downgrade');

        // Content Security Policy básica: permite scripts/estilos locais, evita execução externa via CDNs não controlados
        $response->setHeader('Content-Security-Policy', "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self' data:;");

        return $response;
    }
}
