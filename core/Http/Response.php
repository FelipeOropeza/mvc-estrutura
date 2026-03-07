<?php

declare(strict_types=1);

namespace Core\Http;

class Response
{
    private string $content = '';
    private int $statusCode = 200;
    private array $headers = [];

    public function __construct(string $content = '', int $statusCode = 200, array $headers = [])
    {
        $this->content = $content;
        $this->statusCode = $statusCode;
        $this->headers = $headers;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;
        return $this;
    }

    public function setStatusCode(int $code): self
    {
        $this->statusCode = $code;
        return $this;
    }

    public function setHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * Dispara a Resposta final (Cuidado no FrankenPHP, prefira retorno da Response).
     */
    public function send(string $data = null, int $status = null): void
    {
        if ($data !== null) {
            $this->content = $data;
        }
        if ($status !== null) {
            $this->statusCode = $status;
        }

        if (!headers_sent()) {
            http_response_code($this->statusCode);

            foreach ($this->headers as $name => $value) {
                header("{$name}: {$value}");
            }
        }

        echo $this->content;
        // Removido o 'exit' global para permitir Worker Mode (FrankenPHP) e término gracioso.
    }

    /**
     * Responde como JSON (Imediato, para legado)
     */
    public function json(array|object $data, int $status = 200): void
    {
        $this->setContent(json_encode($data, JSON_UNESCAPED_UNICODE));
        $this->setStatusCode($status);
        $this->setHeader('Content-Type', 'application/json');

        $this->send();
    }

    /**
     * Redireciona para outra URL
     */
    public function redirect(string $url): void
    {
        $this->setContent('');
        $this->setStatusCode(302);
        $this->setHeader('Location', $url);

        $this->send();
    }

    /**
     * Fabricante Estático para redirecionamento sem emitir de imediato
     */
    public static function makeRedirect(string $url, int $status = 302): self
    {
        return new self('', $status, ['Location' => $url]);
    }

    /**
     * Fabricante Estático para redirecionar de volta (referer)
     */
    public static function makeRedirectBack(int $status = 302): self
    {
        $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '/';
        return new self('', $status, ['Location' => $referer]);
    }

    /**
     * Fabricante Estático para JSON sem emitir de imediato
     */
    public static function makeJson(array|object $data, int $status = 200): self
    {
        return new self(
            json_encode($data, JSON_UNESCAPED_UNICODE),
            $status,
            ['Content-Type' => 'application/json']
        );
    }

    /**
     * Adiciona o cabeçalho HX-Trigger para disparar um evento no front-end via HTMX
     */
    public function hxTrigger(string|array $events): self
    {
        if (is_array($events)) {
            $events = json_encode($events);
        }
        return $this->setHeader('HX-Trigger', $events);
    }

    /**
     * Redireciona via HTMX pelo lado do front-end
     */
    public function hxRedirect(string $url): self
    {
        return $this->setHeader('HX-Redirect', $url);
    }

    /**
     * Instrui o HTMX a recarregar a página inteira
     */
    public function hxRefresh(): self
    {
        return $this->setHeader('HX-Refresh', 'true');
    }
}
