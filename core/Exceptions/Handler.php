<?php

declare(strict_types=1);

namespace Core\Exceptions;

use Throwable;
use ErrorException;

class Handler
{
    /**
     * Registra o controlador de exceções e erros globais.
     */
    public function register(): void
    {
        // Garante que o PHP reporte tudo para o nosso manipulador
        error_reporting(E_ALL);

        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);
    }

    /**
     * Converte erros normais do PHP (Warnings, Notices) em Exceções para podermos tratá-los unificados.
     * 
     * @param int $level
     * @param string $message
     * @param string $file
     * @param int $line
     * @return void
     * @throws ErrorException
     */
    public function handleError(int $level, string $message, string $file = '', int $line = 0): void
    {
        // Verificamos se o erro reportado está incluso no nível de error_reporting atual
        if (error_reporting() & $level) {
            throw new ErrorException($message, 0, $level, $file, $line);
        }
    }

    /**
     * Captura qualquer exceção não tratada na aplicação formatada
     * como Response e envia para a saída padrão (Fora de contexto Kernel).
     * 
     * @param Throwable $exception
     * @return void
     */
    public function handleException(Throwable $exception): void
    {
        $response = $this->renderException($exception);
        $response->send();
    }

    /**
     * Transforma qualquer Exceção em um Objeto Response Perfeito.
     * Usado fortemente pelo Kernel HTTP para previnir crashes fatais em servidores assíncronos.
     * 
     * @param Throwable $exception
     * @return \Core\Http\Response
     */
    public function renderException(Throwable $exception): \Core\Http\Response
    {
        // Descobre o código de status HTTP (padrão 500 se não reconhecido)
        $code = $exception->getCode();
        if ($code < 100 || $code >= 600) {
            $code = 500;
        }

        // Verifica se quer retornar JSON (para API) ou HTML
        $isApi = (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) ||
            (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/api/') === 0);

        // Se for um Erro de Validação Limpo, Redirecionamos ou Formatamos o DTO sem Logar como Alerta
        if ($exception instanceof \Core\Exceptions\ValidationException) {
            if ($isApi) {
                return \Core\Http\Response::makeJson([
                    'status' => 'error', 
                    'message' => $exception->getMessage(), 
                    'errors' => $exception->errors
                ], 422);
            } else {
                $_SESSION['_flash_errors'] = $exception->errors;
                $_SESSION['_flash_old'] = $exception->oldInput;
                $referer = $_SERVER['HTTP_REFERER'] ?? '/';
                
                return \Core\Http\Response::makeRedirect($referer);
            }
        }

        // Se NÃO for validação, consideramos um ERRO DO SISTEMA! 
        // Salva silenciosamente a exceção real para os devs poderem espiar o log depois!
        logger()->error($exception->getMessage(), [
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'class' => get_class($exception)
        ]);

        // Busca se APP_DEBUG = true (por padrão é true se não encontrar)
        $debug = function_exists('env') ? env('APP_DEBUG', true) : true;
        if (is_string($debug)) {
            $debug = filter_var($debug, FILTER_VALIDATE_BOOLEAN);
        }

        if ($isApi) {
            return $this->renderJson($exception, (int) $code, (bool) $debug);
        } else {
            return $this->renderHtml($exception, (int) $code, (bool) $debug);
        }
    }

    /**
     * Retorna a resposta de erro em formato JSON (Objeto Response).
     */
    private function renderJson(Throwable $exception, int $code, bool $debug): \Core\Http\Response
    {
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

        return \Core\Http\Response::makeJson($response, $code);
    }

    /**
     * Retorna a resposta de erro em formato HTML (Objeto Response).
     */
    private function renderHtml(Throwable $exception, int $code, bool $debug): \Core\Http\Response
    {
        if ($debug) {
            // Tela de erro detalhada para desenvolvimento
            $content = '<style>
                body { font-family: system-ui, -apple-system, sans-serif; background-color: #f3f4f6; color: #111827; margin: 0; padding: 2rem; }
                .container { max-width: 1200px; margin: 0 auto; background: #fff; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1); }
                h1 { color: #dc2626; margin-top: 0; font-size: 1.5rem; word-break: break-all; }
                .meta { background: #fef2f2; color: #991b1b; padding: 1rem; border-radius: 4px; margin-bottom: 2rem; font-weight: 500; font-size: 1.125rem;}
                .file { background: #f9fafb; padding: 1rem; border-radius: 4px; border-left: 4px solid #9ca3af; margin-bottom: 1rem; word-break: break-all; color: #4b5563;}
                .trace { background: #1f2937; color: #e5e7eb; padding: 1rem; border-radius: 4px; overflow-x: auto; font-size: 0.875rem; line-height: 1.5; }
            </style>';
            $content .= '<div class="container">';
            $content .= "<h1>" . get_class($exception) . "</h1>";
            $content .= "<div class='meta'>Mensagem: " . htmlspecialchars($exception->getMessage()) . "</div>";
            $content .= "<div class='file'><strong>Arquivo:</strong> " . $exception->getFile() . " <br><strong>Linha:</strong> " . $exception->getLine() . "</div>";
            $content .= "<h3>Stack Trace:</h3>";
            $content .= "<pre class='trace'>" . htmlspecialchars($exception->getTraceAsString()) . "</pre>";
            $content .= '</div>';
        } else {
            // Tela genérica de erro para o usuário em Produção
            $content = "<div style='font-family: system-ui, -apple-system, sans-serif; text-align: center; padding: 100px 20px;'>";
            $content .= "<h1 style='color: #374151; font-size: 6rem; margin: 0;'>$code</h1>";
            $content .= "<p style='color: #6b7280; font-size: 1.5rem; margin-top: 10px;'>Ocorreu um erro inesperado.</p>";
            $content .= "</div>";
        }
        
        return new \Core\Http\Response($content, $code);
    }
}
