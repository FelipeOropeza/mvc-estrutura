<?php

namespace App\DTOs\Auth;

use Core\Validation\DataTransferObject;
use Core\Attributes\Required;
use Core\Attributes\Email;

class LoginDTO extends DataTransferObject
{
    #[Required(message: 'O e-mail é obrigatório.')]
    #[Email(message: 'Forneça um e-mail válido.')]
    public string $email;

    #[Required(message: 'A senha é obrigatória.')]
    public string $senha;
}
