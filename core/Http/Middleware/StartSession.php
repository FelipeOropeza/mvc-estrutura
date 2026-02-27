<?php

declare(strict_types=1);

namespace Core\Http\Middleware;

use Closure;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\Session;

class StartSession
{
    protected Session $session;

    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    public function handle(Request $request, Closure $next): Response
    {
        // 1. Inicia a sessão
        $this->session->start();

        // 2. Injeta a sessão no request para acesso prático
        $request->setSession($this->session);

        // 3. Executa o próximo middleware/rota
        $response = $next($request);

        // 4. Limpa e atualiza dados da sessão (ex: envelhece o Flash)
        $this->session->ageFlashData();

        return $response;
    }
}
