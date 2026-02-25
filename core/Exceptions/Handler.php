<?php

namespace Core\Exceptions;

use Throwable;
use ErrorException;

class Handler
{
    /**
     * Registra o controlador de exceções e erros globais.
     */
    public function register()
    {
        // Garante que o PHP reporte tudo para o nosso manipulador
        error_reporting(E_ALL);

        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);
    }

    /**
     * Converte erros normais do PHP (Warnings, Notices) em Exceções para podermos tratá-los unificados.
     */
    public function handleError($level, $message, $file = '', $line = 0)
    {
        // Verificamos se o erro reportado está incluso no nível de error_reporting atual
        if (error_reporting()& $level) {
            throw new ErrorException($message, 0, $level, $file, $line);
        }
    }

    /**
     * Captura qualquer exceção não tratada na aplicação.
     */
    public function handleException(Throwable $exception)
    {
        // Descobre o código de status HTTP (padrão 500)
        $code = $exception->getCode();
        if ($code < 100 || $code >= 600) {
            $code = 500;
        }

        http_response_code($code);

        // Verifica se quer retornar JSON (para API) ou HTML
        $isApi = (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) ||
            (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/api/') === 0);

        if ($exception instanceof \Core\Exceptions\ValidationException) {
            if ($isApi) {
                http_response_code(422);
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => $exception->getMessage(), 'errors' => $exception->errors]);
                exit;
            }
            else {
                $_SESSION['_flash_errors'] = $exception->errors;
                $_SESSION['_flash_old'] = $exception->oldInput;
                $referer = $_SERVER['HTTP_REFERER'] ?? '/';
                header("Location: $referer");
                exit;
            }
        }

        // Descobre o código de status HTTP (padrão 500)

        // Busca se APP_DEBUG = true (por padrão é true se não encontrar)
        $debug = function_exists('env') ? env('APP_DEBUG', true) : true;
        // String to boolean converstion
        if (is_string($debug)) {
            $debug = filter_var($debug, FILTER_VALIDATE_BOOLEAN);
        }

        if ($isApi) {
            $this->renderJson($exception, $code, $debug);
        }
        else {
            $this->renderHtml($exception, $code, $debug);
        }
    }

    /**
     * Retorna a resposta de erro em formato JSON.
     */
    private function renderJson(Throwable $exception, int $code, bool $debug)
    {
        header('Content-Type: application/json');

        $response = [
            'status' => 'error',
            'message' => $debug ? $exception->getMessage() : 'Erro interno no servidor.',
        ];

        if ($debug) {
            $response['exception'] = get_class($exception);
            $response['file'] = $exception->getFile();
            $response['line'] = $exception->getLine();
            $response['trace'] = $exception->getTrace();
        }

        echo json_encode($response, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * Retorna a resposta de erro em formato HTML.
     */
    private function renderHtml(Throwable $exception, int $code, bool $debug)
    {
        if ($debug) {
            // Tela de erro detalhada para desenvolvimento
            echo '<style>
                body { font-family: system-ui, -apple-system, sans-serif; background-color: #f3f4f6; color: #111827; margin: 0; padding: 2rem; }
                .container { max-width: 1200px; margin: 0 auto; background: #fff; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1); }
                h1 { color: #dc2626; margin-top: 0; font-size: 1.5rem; word-break: break-all; }
                .meta { background: #fef2f2; color: #991b1b; padding: 1rem; border-radius: 4px; margin-bottom: 2rem; font-weight: 500; font-size: 1.125rem;}
                .file { background: #f9fafb; padding: 1rem; border-radius: 4px; border-left: 4px solid #9ca3af; margin-bottom: 1rem; word-break: break-all; color: #4b5563;}
                .trace { background: #1f2937; color: #e5e7eb; padding: 1rem; border-radius: 4px; overflow-x: auto; font-size: 0.875rem; line-height: 1.5; }
            </style>';
            echo '<div class="container">';
            echo "<h1>" . get_class($exception) . "</h1>";
            echo "<div class='meta'>Mensagem: " . htmlspecialchars($exception->getMessage()) . "</div>";
            echo "<div class='file'><strong>Arquivo:</strong> " . $exception->getFile() . " <br><strong>Linha:</strong> " . $exception->getLine() . "</div>";
            echo "<h3>Stack Trace:</h3>";
            echo "<pre class='trace'>" . htmlspecialchars($exception->getTraceAsString()) . "</pre>";
            echo '</div>';
        }
        else {
            // Tela genérica de erro para o usuário em Produção
            echo "<div style='font-family: system-ui, -apple-system, sans-serif; text-align: center; padding: 100px 20px;'>";
            echo "<h1 style='color: #374151; font-size: 6rem; margin: 0;'>$code</h1>";
            echo "<p style='color: #6b7280; font-size: 1.5rem; margin-top: 10px;'>Ocorreu um erro inesperado.</p>";
            echo "</div>";
        }
        exit;
    }
}
