<?php

namespace App\Models;

class User
{
    /**
     * Exemplo de modelo padrão do motor MVC.
     * Na vida real isso pode estender um Core\Model que lida com PDO DB.
     *
     * @var string
     */
    protected string $table = 'users';

    public function create(array $data)
    {
        // ... implementação de INSERT via PDO
        return true;
    }

    public function find(int $id)
    {
        // ... implementação de SELECT via PDO
        return [];
    }
}
