<?php

namespace App\Services;

use App\Models\Usuario;
use App\DTOs\Auth\LoginDTO;
use App\DTOs\Auth\RegisterDTO;

class AuthService
{
    private Usuario $usuarioModel;

    public function __construct()
    {
        $this->usuarioModel = new Usuario();
    }

    public function login(LoginDTO $dto): object
    {
        $usuario = $this->usuarioModel->where('email', '=', $dto->email)->first();

        if (!$usuario || !password_verify($dto->senha, $usuario->senha)) {
            fail_validation(['email' => 'As credenciais informadas são inválidas.']);
        }

        return $usuario;
    }

    public function registrar(RegisterDTO $dto): object
    {
        // Validação adicional de confirmação de senha
        if ($dto->senha !== $dto->senha_confirmacao) {
            fail_validation(['senha_confirmacao' => 'A confirmação de senha não coincide.']);
        }

        if ($this->usuarioModel->where('email', '=', $dto->email)->first()) {
            fail_validation(['email' => 'Este e-mail já está em uso.']);
        }

        $data = $dto->toArray();
        unset($data['senha_confirmacao']);
        
        $data['senha'] = password_hash($data['senha'], PASSWORD_DEFAULT);
        $id = $this->usuarioModel->insert($data);

        return (object)[
            'id' => $id,
            'nome' => $dto->nome,
            'email' => $dto->email
        ];
    }
}
