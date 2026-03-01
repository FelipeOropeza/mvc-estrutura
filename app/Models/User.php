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

    // 1. Proteção de Mass Assignment (Atribuição em Massa permitida)
    protected array $fillable = ['nome', 'email', 'password', 'saldo'];

    // 2. Os Timestamps (created_at) já vêm true por padrão da Model PAI!
    // public bool $timestamps = true;

    // 3. Modificando Hooks (Gatilhos) para Encriptar a Senha sempre!
    protected function beforeInsert(array $data): array
    {
        if (isset($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        return $data;
    }

    protected function beforeUpdate(array $data): array
    {
        // Só encrypta se o cara mandou atualizar a senha 
        // e ela ainda não é um hash criptografado
        if (isset($data['password']) && password_get_info($data['password'])['algoName'] === 'unknown') {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        return $data;
    }
}
