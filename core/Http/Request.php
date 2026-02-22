<?php

namespace Core\Http;

class Request
{
    /**
     * Parâmetros customizados injetados por middlewares ou rotas 
     * Ex: Usuário autenticado, variáveis específicas...
     */
    public array $attributes = [];

    /**
     * Retorna um dado do corpo da requisição (POST) ou da URL (GET).
     */
    public function get(string $key, $default = null)
    {
        return $_REQUEST[$key] ?? $default;
    }

    /**
     * Retorna todos os dados enviados por formulário ou JSON.
     */
    public function all(): array
    {
        // Se a requisição for JSON, pegamos o body processado
        if (str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json')) {
            $json = json_decode(file_get_contents('php://input'), true);
            if (is_array($json)) {
                return $json;
            }
        }

        return $_REQUEST;
    }

    /**
     * Retorna o método HTTP da requisição atual.
     */
    public function method(): string
    {
        return $_SERVER['REQUEST_METHOD'] ?? 'GET';
    }

    /**
     * Retorna o caminho da URL (URI).
     */
    public function path(): string
    {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $scriptName = dirname($_SERVER['SCRIPT_NAME']);

        if ($scriptName !== '/' && strpos($uri, $scriptName) === 0) {
            $uri = substr($uri, strlen($scriptName));
        }

        return '/' . trim($uri, '/');
    }

    /**
     * Verifica se a requisição está esperando JSON como resposta (APIs)
     */
    public function wantsJson(): bool
    {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

        return str_contains($accept, 'application/json') || str_contains($contentType, 'application/json');
    }

    /**
     * Retorna a URL da página anterior (útil para redirecionar de volta em erros de formulário)
     */
    public function referer(): string
    {
        return $_SERVER['HTTP_REFERER'] ?? '/';
    }
}
