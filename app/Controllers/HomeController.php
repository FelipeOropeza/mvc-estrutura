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

    public function testeMiddleware(\Core\Http\Request $request)
    {
        // Se quisermos imprimir na tela como uma API devolvendo JSON puro:
        response()->json([
            'status' => 'sucesso',
            'mensagem' => 'Acesso liberado pelo middleware!',
            'dados_injetados_no_meio_do_caminho' => $request->attributes['middleware_teste']
        ]);
    }
}
