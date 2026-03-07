<?php

namespace App\Controllers;

use Core\Http\Controller;
use Core\Attributes\Route\Get;
use Core\Attributes\Route\Post;
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
            'status' => $status,
            'cliques' => session('cliques_demo', 0)
        ];

        // Renderizando a view usando o novo helper
        return view('home', $data);
    }

    #[Post('/api/comp-clique')]
    public function updateComponent()
    {
        // Pega da sessão quantos cliques deu (apenas pra simular ESTADO no servidor)
        $cliques = session('cliques_demo', 0);
        $cliques++;
        
        session()->set('cliques_demo', $cliques);

        // Retorna APENAS o componente (view parcial) sem layout!
        // Como implementamos o request()->isHtmx() lá na Engine, ele será renderizado puro.
        return view('components/demo_htmx', ['cliques' => $cliques]);
    }
}
