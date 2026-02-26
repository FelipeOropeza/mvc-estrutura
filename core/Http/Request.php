<?php

declare(strict_types=1);

namespace Core\Http;

class Request
{
    /**
     * Parâmetros customizados injetados por middlewares ou rotas 
     * Ex: Usuário autenticado, variáveis específicas...
     * 
     * @var array
     */
    public array $attributes = [];

    /**
     * Retorna um dado do corpo da requisição (POST) ou da URL (GET).
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $_REQUEST[$key] ?? $default;
    }

    /**
     * Retorna todos os dados enviados por formulário ou JSON.
     * 
     * @return array
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
     * 
     * @return string
     */
    public function method(): string
    {
        return (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    /**
     * Retorna o caminho da URL (URI).
     * 
     * @return string
     */
    public function path(): string
    {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $scriptName = dirname($_SERVER['SCRIPT_NAME'] ?? '');

        if ($scriptName !== '/' && strpos((string) $uri, (string) $scriptName) === 0) {
            $uri = substr((string) $uri, strlen((string) $scriptName));
        }

        return '/' . trim((string) $uri, '/');
    }

    /**
     * Verifica se a requisição está esperando JSON como resposta (APIs)
     * 
     * @return bool
     */
    public function wantsJson(): bool
    {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

        return str_contains($accept, 'application/json') || str_contains($contentType, 'application/json');
    }

    /**
     * Retorna a URL da página anterior (útil para redirecionar de volta em erros de formulário)
     * 
     * @return string
     */
    public function referer(): string
    {
        return (string) ($_SERVER['HTTP_REFERER'] ?? '/');
    }
}
