<?php

namespace App\Models;

use Core\Database\Model;
use Core\Attributes\Required;
use Core\Attributes\Email;
use Core\Attributes\IsFloat;
use Core\Attributes\Min;

class User extends Model
{
    protected $table = 'users';

    // Propriedades publicas mapeando as colunas da tabela "users"
    public ?int $id = null;

    #[Required]
    public ?string $nome = null;

    #[Required]
    #[Email]
    public ?string $email = null;

    #[Required]
    #[Min(8)]
    public ?string $password = null;

    #[Required]
    #[IsFloat(7, 2)]
    public ?float $saldo = null;

    public ?string $created_at = null;
    public ?string $updated_at = null;
}
