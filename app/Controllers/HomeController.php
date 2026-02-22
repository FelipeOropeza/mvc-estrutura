<?php

namespace App\Controllers;

use Core\Http\Controller;

class HomeController extends Controller
{
    public function index()
    {
        $data = [
            'name' => 'Visitante',
            'title' => 'Minha Estrutura MVC Simples'
        ];

        // Renderizando a view usando o novo helper (Estilo Leaf)
        return view('home', $data);
    }
}
