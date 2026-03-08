<?php

namespace App\Controllers;

use Core\Http\Controller;
use Core\Attributes\Route\Get;

class HomeController extends Controller
{
    #[Get('/home')]
    public function index()
    {
        $data = [
            'title' => 'Minha Estrutura MVC Simples',
        ];

        return view('home', $data);
    }
}
