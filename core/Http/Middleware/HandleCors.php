<?php

declare(strict_types=1);

namespace Core\Http\Middleware;

use Core\Http\Request;
use Core\Http\Response;
use Closure;

class HandleCors
{
    /**
     * Origens permitidas. Use '*' para liberar tudo (não recomendado em produção).
     * Defina origens específicas via APP_CORS_ORIGINS no .env.
     * Exemplo: APP_CORS_ORIGINS=http://localhost:3000,https://meuapp.com
     */
    private array $allowedOrigins;
    private array $allowedMethods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'];
    private array $allowedHeaders = ['Content-Type', 'Authorization', 'X-Requested-With', 'Accept'];

    public function __construct()
    {
        $origins = env('APP_CORS_ORIGINS', '*');
        $this->allowedOrigins = $origins === '*' ? ['*'] : array_map('trim', explode(',', $origins));
    }

    public function handle(Request $request, Closure $next): mixed
    {
        $origin = $request->server['HTTP_ORIGIN'] ?? '';

        // Requisição preflight do browser (OPTIONS) — responde imediatamente sem passar pela app
        if ($request->method() === 'OPTIONS') {
            return $this->buildCorsResponse(new Response('', 204), $origin);
        }

        /** @var Response $response */
        $response = $next($request);

        return $this->buildCorsResponse($response, $origin);
    }

    private function buildCorsResponse(Response $response, string $origin): Response
    {
        if (in_array('*', $this->allowedOrigins)) {
            $response->setHeader('Access-Control-Allow-Origin', '*');
        } elseif ($origin && in_array($origin, $this->allowedOrigins)) {
            $response->setHeader('Access-Control-Allow-Origin', $origin);
            $response->setHeader('Vary', 'Origin');
        }

        $response->setHeader('Access-Control-Allow-Methods', implode(', ', $this->allowedMethods));
        $response->setHeader('Access-Control-Allow-Headers', implode(', ', $this->allowedHeaders));
        $response->setHeader('Access-Control-Max-Age', '86400');

        return $response;
    }
}
