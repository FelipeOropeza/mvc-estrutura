<?php

namespace App\Models;

use Core\Database\Model;

class User extends Model
{
    /**
     * Opcional: Especifique o nome da tabela no banco
     * Se não informar nada, ele entende que a tabela se chama "{NomeDoModel}s", ex: "users".
     */
    protected $table = 'users';
}
