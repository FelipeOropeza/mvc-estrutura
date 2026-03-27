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
    protected ?Session $session = null;

    public function __construct(array $query = [], array $post = [], array $server = [], array $cookies = [], array $files = [], string $content = '')
    {
        $this->query = $query;
        $this->post = $post;
        $this->server = $server;
        $this->cookies = $cookies;
        $this->files = $this->normalizeFiles($files);
        $this->content = $content;
    }

    /**
     * Normaliza as submissões de $_FILES em objetos UploadedFile
     */
    protected function normalizeFiles(array $files): array
    {
        $normalized = [];
        foreach ($files as $key => $file) {
            if (isset($file['name'], $file['type'], $file['tmp_name'], $file['error'], $file['size']) && is_string($file['name'])) {
                if ($file['error'] !== UPLOAD_ERR_NO_FILE) {
                    $normalized[$key] = new UploadedFile(
                        $file['tmp_name'],
                        $file['name'],
                        $file['type'],
                        $file['error'],
                        $file['size']
                    );
                }
            }
        }
        return $normalized;
    }

    /**
     * Captura a requisição atual do servidor e empacota neste objeto.
     */
    public static function capture(): self
    {
        return new self($_GET, $_POST, $_SERVER, $_COOKIE, $_FILES, file_get_contents('php://input') ?: '');
    }

    /**
     * Verifica se um campo existe na requisição.
     */
    public function has(string $key): bool
    {
        return isset($this->post[$key]) || isset($this->query[$key]) || isset($this->files[$key]);
    }

    /**
     * Retorna um dado do corpo da requisição (POST) ou da URL (GET).
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->files[$key] ?? $this->post[$key] ?? $this->query[$key] ?? $default;
    }

    /**
     * Alias para o método get().
     */
    public function input(string $key, mixed $default = null): mixed
    {
        return $this->get($key, $default);
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
        return array_merge($this->query, $this->post, $this->files);
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
        $scriptName = str_replace('\\', '/', dirname($this->server['SCRIPT_NAME'] ?? ''));
        if ($scriptName === '\\' || $scriptName === '.') {
            $scriptName = '/';
        }

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
     * Verifica se a requisição é direcionada para a API baseada no path ou headers.
     */
    public function isApi(): bool
    {
        return str_starts_with($this->path(), '/api') || $this->wantsJson();
    }

    /**
     * Retorna a URL da página anterior.
     */
    public function referer(): string
    {
        return (string) ($this->server['HTTP_REFERER'] ?? '/');
    }

    public function setSession(Session $session): void
    {
        $this->session = $session;
    }

    public function session(): ?Session
    {
        return $this->session;
    }

    /**
     * Verifica se a requisição foi feita pelo HTMX
     */
    public function isHtmx(): bool
    {
        return isset($this->server['HTTP_HX_REQUEST']) && $this->server['HTTP_HX_REQUEST'] === 'true';
    }

    /**
     * Verifica se a requisição foi feita via AJAX (XMLHttpRequest)
     */
    public function isAjax(): bool
    {
        return isset($this->server['HTTP_X_REQUESTED_WITH']) && $this->server['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
    }

    /**
     * Retorna o valor de um cabeçalho da requisição.
     * Verifica $_SERVER primeiro, depois getallheaders() como fallback.
     * Necessário pois o servidor embutido do PHP não repassa HTTP_AUTHORIZATION via $_SERVER.
     */
    public function header(string $key, mixed $default = null): mixed
    {
        $serverKey = 'HTTP_' . strtoupper(str_replace('-', '_', $key));

        if (isset($this->server[$serverKey])) {
            return $this->server[$serverKey];
        }

        // Fallback: lê os headers reais via getallheaders() (funciona no CLI e Apache)
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            // Busca case-insensitive
            foreach ($headers as $name => $value) {
                if (strcasecmp($name, $key) === 0) {
                    return $value;
                }
            }
        }

        return $default;
    }
}
