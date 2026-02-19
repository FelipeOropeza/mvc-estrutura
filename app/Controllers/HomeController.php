<?php

namespace App\Controllers;

use Core\Controller;

class HomeController extends Controller
{
    public function index()
    {
        $data = [
            'name' => 'Visitante',
            'title' => 'Minha Estrutura MVC Simples'
        ];

        $this->view('home', $data);
    }
}
