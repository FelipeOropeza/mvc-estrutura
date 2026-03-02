<?php

namespace App\Controllers;

use Core\Http\Controller;
use Core\Attributes\Route\Get;
use App\Services\MeuPrimeiroService;

class HomeController extends Controller
{
    private $meuService;

    /**
     * O Container do framework injeta essa dependência magicamente!
     */
    public function __construct(MeuPrimeiroService $meuService)
    {
        $this->meuService = $meuService;
    }

    #[Get('/home')]
    public function index()
    {
        // Usamos o service injetado
        $status = $this->meuService->execute();

        $data = [
            'name' => 'Visitante',
            'title' => 'Minha Estrutura MVC Simples',
            'status' => $status
        ];

        // Renderizando a view usando o novo helper
        return view('home', $data);
    }
}
