<?php

declare(strict_types=1);

namespace Core\Http;

class Response
{
    /**
     * Retorna uma string comum como texto.
     * 
     * @param string $data
     * @param int $status
     * @return void
     */
    public function send(string $data, int $status = 200): void
    {
        http_response_code($status);
        echo $data;
        exit;
    }

    /**
     * Envia uma resposta JSON, útil para APIs (O clássico app->response->json do Leaf).
     * 
     * @param array $data
     * @param int $status
     * @return void
     */
    public function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Redireciona para outra URL.
     * 
     * @param string $url
     * @return void
     */
    public function redirect(string $url): void
    {
        header("Location: $url");
        exit;
    }
}
