<?php

namespace App\Models;

use Core\Database\Model;
use Core\Attributes\Required;
use Core\Attributes\Email;
use Core\Attributes\IsFloat;
use Core\Attributes\Min;
use Core\Attributes\Hash;

class User extends Model
{
    protected $table = 'users';
    protected array $fillable = ['nome', 'email', 'password', 'saldo'];

    // Propriedades publicas mapeando as colunas da tabela "users"
    public ?int $id = null;

    #[Required]
    public ?string $nome = null;

    #[Required]
    #[Email]
    public ?string $email = null;

    #[Required]
    #[Min(8)]
    #[Hash]
    public ?string $password = null;

    #[Required]
    #[IsFloat(7, 2)]
    public ?float $saldo = null;

    public ?string $created_at = null;
    public ?string $updated_at = null;
}
