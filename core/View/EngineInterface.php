<?php

namespace Core\View;

interface EngineInterface
{
    /**
     * Renderiza o template com os dados fornecidos.
     *
     * @param string $view Nome da view (ex: 'home' ou 'home.html.twig')
     * @param array $data Variaveis que a view recebe
     * @return void  (imprime o conteudo na tela)
     */
    public function render(string $view, array $data = []): void;
}
