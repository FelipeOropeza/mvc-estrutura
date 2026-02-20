<?php

namespace Core\Http;

use Core\View\PhpEngine;
use Core\View\TwigEngine;

class Response
{
    /**
     * Retorna uma string comum como texto.
     */
    public function send(string $data, int $status = 200)
    {
        http_response_code($status);
        echo $data;
        exit;
    }

    /**
     * Envia uma resposta JSON, útil para APIs (O clássico app->response->json do Leaf).
     */
    public function json(array $data, int $status = 200)
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Redireciona para outra URL.
     */
    public function redirect(string $url)
    {
        header("Location: $url");
        exit;
    }
}
