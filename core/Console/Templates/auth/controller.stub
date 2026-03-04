<?php

namespace App\Controllers;

use Core\Http\Response;
use App\DTOs\Auth\LoginDTO;
use App\DTOs\Auth\RegisterDTO;
use App\Models\Usuario;

class AuthController
{
    private \App\Services\AuthService $authService;

    public function __construct()
    {
        // No mundo PHP MVC Raiz com Injeção de Dependências, 
        // poderíamos fazer o PHP Auto-Wiring instanciar as DTOs e este Service. 
        // Mas por clareza do boiler, instanciamos na mão caso o seu IoC Container esteja inativo.
        $this->authService = app(\App\Services\AuthService::class);
    }

    public function loginForm()
    {
        return view('auth/login');
    }

    public function login(LoginDTO $dto)
    {
        $usuario = $this->authService->login($dto);

        // Armazena na sessão
        session()->set('user', ['id' => $usuario->id, 'nome' => $usuario->nome, 'email' => $usuario->email]);

        return Response::makeRedirect('/dashboard');
    }

    public function registerForm()
    {
        return view('auth/register');
    }

    public function register(RegisterDTO $dto)
    {
        $usuario = $this->authService->registrar($dto);

        session()->set('user', ['id' => $usuario->id, 'nome' => $usuario->nome, 'email' => $usuario->email]);

        return Response::makeRedirect('/dashboard');
    }

    public function logout()
    {
        session()->remove('user');
        session()->destroy();
        return Response::makeRedirect('/login');
    }
}
