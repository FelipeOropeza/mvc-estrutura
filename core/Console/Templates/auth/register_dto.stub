<?php

namespace App\DTOs\Auth;

use Core\Validation\DataTransferObject;
use Core\Attributes\Required;
use Core\Attributes\Email;
use Core\Attributes\Min;

class RegisterDTO extends DataTransferObject
{
    #[Required(message: 'O nome é obrigatório.')]
    public string $nome;

    #[Required(message: 'O e-mail é obrigatório.')]
    #[Email(message: 'Forneça um e-mail válido.')]
    public string $email;

    #[Required(message: 'A senha é obrigatória.')]
    #[Min(8, message: 'A senha deve ter no mínimo 8 caracteres.')]
    public string $senha;

    #[Required(message: 'A confirmação de senha é obrigatória.')]
    public string $senha_confirmacao;
}
