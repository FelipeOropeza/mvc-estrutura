<?php

declare(strict_types=1);

namespace Core\Http;

class Request
{
    public array $attributes = [];
    public array $query;
    public array $post;
    public array $server;
    public array $cookies;
    public array $files;
    public string $content;

    public function __construct(array $query = [], array $post = [], array $server = [], array $cookies = [], array $files = [], string $content = '')
    {
        $this->query = $query;
        $this->post = $post;
        $this->server = $server;
        $this->cookies = $cookies;
        $this->files = $files;
        $this->content = $content;
    }

    /**
     * Captura a requisição atual do servidor e empacota neste objeto.
     */
    public static function capture(): self
    {
        return new self($_GET, $_POST, $_SERVER, $_COOKIE, $_FILES, file_get_contents('php://input') ?: '');
    }

    /**
     * Retorna um dado do corpo da requisição (POST) ou da URL (GET).
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->post[$key] ?? $this->query[$key] ?? $default;
    }

    /**
     * Retorna todos os dados enviados por formulário ou JSON.
     */
    public function all(): array
    {
        $contentType = $this->server['CONTENT_TYPE'] ?? '';

        if (str_contains($contentType, 'application/json')) {
            $json = json_decode($this->content, true);
            if (is_array($json)) {
                return $json;
            }
        }

        return array_merge($this->query, $this->post);
    }

    /**
     * Retorna o método HTTP da requisição atual.
     */
    public function method(): string
    {
        return (string) ($this->server['REQUEST_METHOD'] ?? 'GET');
    }

    /**
     * Retorna o caminho da URL (URI).
     */
    public function path(): string
    {
        $uri = parse_url($this->server['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $scriptName = dirname($this->server['SCRIPT_NAME'] ?? '');

        if ($scriptName !== '/' && strpos((string) $uri, (string) $scriptName) === 0) {
            $uri = substr((string) $uri, strlen((string) $scriptName));
        }

        return '/' . trim((string) $uri, '/');
    }

    /**
     * Verifica se a requisição está esperando JSON como resposta (APIs)
     */
    public function wantsJson(): bool
    {
        $accept = $this->server['HTTP_ACCEPT'] ?? '';
        $contentType = $this->server['CONTENT_TYPE'] ?? '';

        return str_contains($accept, 'application/json') || str_contains($contentType, 'application/json');
    }

    /**
     * Retorna a URL da página anterior.
     */
    public function referer(): string
    {
        return (string) ($this->server['HTTP_REFERER'] ?? '/');
    }
}
