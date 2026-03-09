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
     * @param \Core\Http\Request|null $request
     * @return \Core\Http\Response
     */
    public function renderException(Throwable $exception, ?\Core\Http\Request $request = null): \Core\Http\Response
    {
        // 1. Limpa qualquer buffer de saída que possa estar aberto (evita erro "sujo" dentro de layouts/views)
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        // Descobre o código de status HTTP (padrão 500 se não reconhecido)
        $code = $exception->getCode();
        if ($code < 100 || $code >= 600) {
            $code = 500;
        }

        // Verifica se quer retornar JSON (para API) ou HTML
        $isApi = $request ? $request->isApi() : (
            (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) ||
            (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/api/') === 0)
        );

        // Se for um Erro de Validação Limpo, Redirecionamos ou Formatamos o DTO sem Logar como Alerta
        if ($exception instanceof \Core\Exceptions\ValidationException) {
            if ($isApi) {
                return \Core\Http\Response::makeJson([
                    'status' => 'error',
                    'message' => $exception->getMessage(),
                    'errors' => $exception->errors
                ], 422);
            } else {
                session()->flash('_flash_errors', $exception->errors);
                session()->flash('_flash_old', $exception->oldInput);
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

        $isCli = php_sapi_name() === 'cli' || php_sapi_name() === 'phpdbg';

        if ($isCli) {
            return $this->renderCli($exception, (int) $code, (bool) $debug);
        } elseif ($isApi) {
            return $this->renderJson($exception, (int) $code, (bool) $debug);
        } else {
            $response = $this->renderHtml($exception, (int) $code, (bool) $debug);

            // Se for uma requisição HTMX que deu erro, forçamos o Retarget para o Body
            // Isso garante que o erro apareça em "tela cheia" e não apenas dentro de um componente
            if (function_exists('request') && request()->isHtmx()) {
                $response->setHeader('HX-Retarget', 'body');
                $response->setHeader('HX-Reswap', 'innerHTML');
            }

            return $response;
        }
    }

    /**
     * Retorna a resposta de erro renderizada para o Terminal (CLI) de forma limpa.
     */
    private function renderCli(Throwable $exception, int $code, bool $debug): \Core\Http\Response
    {
        if ($debug) {
            $content = "\n\033[41m\033[97m ERRO \033[0m " . get_class($exception) . "\n";
            $content .= "\n\033[31mMensagem:\033[0m " . $exception->getMessage() . "\n";
            $content .= "\033[33mArquivo:\033[0m " . $exception->getFile() . ":" . $exception->getLine() . "\n";
            $content .= "\nStack Trace:\n" . $exception->getTraceAsString() . "\n\n";
        } else {
            $content = "\n\033[41m\033[97m ERRO \033[0m Ocorreu um erro inesperado ($code).\n\n";
        }

        return new \Core\Http\Response($content, $code);
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
            // Tela de erro detalhada e AESTHETIC para desenvolvimento
            $content = '
            <!DOCTYPE html>
            <html lang="pt-br">
            <head>
                <meta charset="UTF-8">
                <title>Erro de Execução :: MVC Base</title>
                <style>
                    :root { --bg: #0f172a; --card: #1e293b; --text: #f1f5f9; --muted: #94a3b8; --danger: #ef4444; --accent: #38bdf8; }
                    body { font-family: "Inter", system-ui, -apple-system, sans-serif; background-color: var(--bg); color: var(--text); margin: 0; padding: 2rem; line-height: 1.5; }
                    .container { max-width: 1100px; margin: 0 auto; }
                    .header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem; border-bottom: 1px solid #334155; padding-bottom: 1rem; }
                    h1 { color: var(--danger); font-size: 1.25rem; margin: 0; font-family: monospace; }
                    .status { background: #fee2e2; color: #b91c1c; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.875rem; font-weight: bold; }
                    .error-box { background: var(--card); border-radius: 12px; padding: 2rem; border-left: 6px solid var(--danger); box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.3); margin-bottom: 2rem; }
                    .message { font-size: 1.5rem; font-weight: 700; margin-bottom: 1rem; color: #fff; }
                    .location { color: var(--muted); font-family: monospace; font-size: 0.95rem; border: 1px solid #334155; padding: 0.75rem; border-radius: 6px; background: #0f172a; }
                    .location strong { color: var(--accent); }
                    .trace-title { display: flex; align-items: center; gap: 0.5rem; margin-top: 2rem; margin-bottom: 1rem; color: var(--muted); text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em; font-weight: bold; }
                    .trace { background: #020617; color: #cbd5e1; padding: 1.5rem; border-radius: 8px; overflow-x: auto; font-size: 0.85rem; font-family: "Fira Code", "Cascadia Code", monospace; border: 1px solid #334155; white-space: pre-wrap; word-break: break-all; }
                    .stack-line { color: #475569; margin-right: 0.5rem; user-select: none; }
                </style>
            </head>
            <body>
                <div class="container">
                    <div class="header">
                        <h1>' . get_class($exception) . '</h1>
                        <span class="status">HTTP ' . $code . '</span>
                    </div>
                    
                    <div class="error-box">
                        <div class="message">' . htmlspecialchars($exception->getMessage()) . '</div>
                        <div class="location">
                            <strong>Local:</strong> ' . $exception->getFile() . ' <strong>na linha</strong> ' . $exception->getLine() . '
                        </div>
                    </div>

                    <div class="trace-title">
                        <span>Stack Trace</span>
                    </div>
                    <pre class="trace">' . htmlspecialchars($exception->getTraceAsString()) . '</pre>
                    
                    <footer style="margin-top: 3rem; text-align: center; color: var(--muted); font-size: 0.875rem;">
                        MVC Base Engineering &bull; Debug Mode Active
                    </footer>
                </div>
            </body>
            </html>';
        } else {
            // Tela genérica de erro para o usuário em Produção (Clean & Pro)
            $content = "
            <body style='font-family: system-ui, sans-serif; background: #f9fafb; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0;'>
                <div style='text-align: center; max-width: 500px;'>
                    <h1 style='color: #1f2937; font-size: 8rem; margin: 0; line-height: 1;'>$code</h1>
                    <h2 style='color: #4b5563; margin-top: 0;'>Ops! Algo deu errado.</h2>
                    <p style='color: #6b7280;'>Nossa equipe foi notificada e estamos trabalhando nisso. Por favor, tente novamente em alguns instantes.</p>
                    <a href='/' style='display: inline-block; background: #2563eb; color: #fff; padding: 0.75rem 1.5rem; border-radius: 6px; text-decoration: none; font-weight: 500; margin-top: 1.5rem;'>Voltar ao Início</a>
                </div>
            </body>";
        }

        return new \Core\Http\Response($content, $code);
    }
}
