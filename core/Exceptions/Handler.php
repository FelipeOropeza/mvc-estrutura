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

        // Descobre o código de status HTTP
        $code = $exception->getCode();
        
        // Se for erro de banco, tentamos ser mais específicos no código HTTP (Conflict 409 para Unique)
        if ($exception instanceof \PDOException && ($code === '23000' || str_contains($exception->getMessage(), '1062'))) {
            $code = 409; 
        }

        if ($code < 100 || $code >= 600 || is_string($code)) {
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
                $referer = request()->referer();
                $originPath = parse_url($referer, PHP_URL_PATH) ?: request()->path();

                session()->flash('errors', $exception->errors);
                session()->flash('errors_origin', $originPath);

                $cleanOld = array_filter($exception->oldInput, fn($v) => !($v instanceof \Core\Http\UploadedFile));
                session()->flash('old', $cleanOld);
                session()->flash('old_origin', $originPath);

                $referer = $_SERVER['HTTP_REFERER'] ?? '/';
                return \Core\Http\Response::makeRedirect($referer);
            }
        }

        // --- NOVO: Captura silenciosa de erros de banco para converter em mensagens amigáveis em Produção ---
        if ($exception instanceof \PDOException && !env('APP_DEBUG', true)) {
             // Se não for debug, logamos o erro real mas mostramos algo sutil se for duplicata
             if ($exception->getCode() === '23000') {
                 session()->flash('errors', ['database' => ['Este registro já existe em nossa base de dados.']]);
                 return \Core\Http\Response::makeRedirect($_SERVER['HTTP_REFERER'] ?? '/');
             }
        }

        // Salva silenciosamente a exceção real para os devs poderem espiar o log depois!
        logger()->error($exception->getMessage(), [
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'class' => get_class($exception),
            'sql_state' => ($exception instanceof \PDOException) ? $exception->getCode() : null
        ]);

        // Busca se APP_DEBUG = true
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

        if ($exception instanceof \PDOException) {
            $response['db_error'] = [
                'state' => $exception->getCode(),
                'hint' => $this->getDbHint($exception)
            ];
        }

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
            $dbDiagnosis = '';
            if ($exception instanceof \PDOException) {
                $hint = $this->getDbHint($exception);
                $dbDiagnosis = '
                <div class="db-box">
                    <div class="db-title">🔍 DIAGNÓSTICO DE BANCO DE DADOS</div>
                    <div class="db-hint">' . $hint . '</div>
                    <div class="db-code">SQLSTATE: <code>' . $exception->getCode() . '</code></div>
                </div>';
            }

            $content = '
            <!DOCTYPE html>
            <html lang="pt-br">
            <head>
                <meta charset="UTF-8">
                <title>Erro de Execução :: MVC Base</title>
                <style>
                    :root { --bg: #0f172a; --card: #1e293b; --text: #f1f5f9; --muted: #94a3b8; --danger: #ef4444; --accent: #38bdf8; --warning: #f59e0b; }
                    body { font-family: "Inter", system-ui, -apple-system, sans-serif; background-color: var(--bg); color: var(--text); margin: 0; padding: 2rem; line-height: 1.5; }
                    .container { max-width: 1100px; margin: 0 auto; }
                    .header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem; border-bottom: 1px solid #334155; padding-bottom: 1rem; }
                    h1 { color: var(--danger); font-size: 1.25rem; margin: 0; font-family: monospace; }
                    .status { background: #fee2e2; color: #b91c1c; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.875rem; font-weight: bold; }
                    .error-box { background: var(--card); border-radius: 12px; padding: 2rem; border-left: 6px solid var(--danger); box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.3); margin-bottom: 1rem; }
                    .message { font-size: 1.5rem; font-weight: 700; margin-bottom: 1rem; color: #fff; }
                    .location { color: var(--muted); font-family: monospace; font-size: 0.95rem; border: 1px solid #334155; padding: 0.75rem; border-radius: 6px; background: #0f172a; }
                    .location strong { color: var(--accent); }
                    
                    /* DB Diagnosis Styling */
                    .db-box { background: #451a03; border: 1px solid var(--warning); border-radius: 8px; padding: 1.5rem; margin-bottom: 2rem; border-left: 6px solid var(--warning); }
                    .db-title { color: var(--warning); font-weight: 800; font-size: 0.75rem; margin-bottom: 0.5rem; letter-spacing: 0.05em; }
                    .db-hint { color: #fed7aa; font-size: 1.1rem; margin-bottom: 0.75rem; font-weight: 500; }
                    .db-code { color: #92400e; font-size: 0.8rem; font-family: monospace; }
                    .db-code code { background: #000; padding: 2px 5px; border-radius: 4px; color: var(--warning); }

                    .trace-title { display: flex; align-items: center; gap: 0.5rem; margin-top: 2rem; margin-bottom: 1rem; color: var(--muted); text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em; font-weight: bold; }
                    .trace { background: #020617; color: #cbd5e1; padding: 1.5rem; border-radius: 8px; overflow-x: auto; font-size: 0.85rem; font-family: "Fira Code", "Cascadia Code", monospace; border: 1px solid #334155; white-space: pre-wrap; word-break: break-all; }
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

                    ' . $dbDiagnosis . '

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

    /**
     * Tenta adivinhar o problema do banco de dados para ajudar o desenvolvedor.
     * Cobre desde erros de conexão até violações de integridade genéricas.
     */
    private function getDbHint(\PDOException $e): string
    {
        $msg = $e->getMessage();
        $code = (string) $e->getCode();
        $errorInfo = $e->errorInfo ?? [];
        $driverCode = $errorInfo[1] ?? null;

        // 1. VIOLAÇÃO DE INTEGRIDADE (Duplicates, Nulls, FKs)
        if ($code === '23000' || $driverCode === 1062) {
            if (str_contains($msg, 'Duplicate entry') || $driverCode === 1062) {
                return "<strong>Entrada Duplicada:</strong> Você está tentando salvar um valor que já existe em uma coluna com índice <code>UNIQUE</code>. <br><small>Sugestão: Verifique se o registro já não foi criado ou use o atributo <code>#[Unique]</code> no seu DTO.</small>";
            }
            if (str_contains($msg, 'cannot be null') || str_contains($msg, 'Column') && str_contains($msg, 'null')) {
                return "<strong>Valor Obrigatório Ausente:</strong> Você tentou salvar um valor nulo em uma coluna que não aceita <code>NULL</code>. <br><small>Sugestão: Verifique se todos os campos obrigatórios estão no <code>$fillable</code> da Model ou validados no DTO.</small>";
            }
            if (str_contains($msg, 'a foreign key constraint fails') || in_array($driverCode, [1451, 1452])) {
                return "<strong>Violação de Chave Estrangeira:</strong> Você tentou relacionar este registro com um ID que não existe na outra tabela, ou tentou apagar um registro que possui dependências.";
            }
        }

        // 2. ESTRUTURA (Tabelas ou Colunas faltando)
        if ($code === '42S02' || str_contains($msg, 'Base table or view not found')) {
            return "<strong>Tabela Inexistente:</strong> A tabela solicitada na Model não foi encontrada no banco atual. <br><small>Sugestão: Verifique o nome na propriedade <code>$table</code> da sua Model ou rode as migrations.</small>";
        }
        if ($code === '42S22' || str_contains($msg, 'Unknown column')) {
            return "<strong>Coluna Inexistente:</strong> A query tentou acessar uma coluna que não existe nesta tabela. <br><small>Sugestão: Verifique se o nome do campo no banco coincide com a lógica do seu Model ou QueryBuilder.</small>";
        }

        // 3. CONEXÃO E ACESSO
        if ($code === '1045' || str_contains($msg, 'Access denied for user')) {
            return "<strong>Acesso Negado:</strong> O banco de dados recusou a conexão com o usuário/senha informados. <br><small>Sugestão: Revise as credenciais <code>DB_USERNAME</code> e <code>DB_PASSWORD</code> no seu <code>.env</code>.</small>";
        }
        if (str_contains($msg, 'Connection refused') || str_contains($msg, 'Can\'t connect to MySQL server')) {
            return "<strong>Servidor Indisponível:</strong> O framework não conseguiu estabelecer contato com o servidor de banco de dados. <br><small>Sugestão: Verifique se o serviço (MySQL/Postgres) está rodando e se o <code>DB_HOST</code> está correto.</small>";
        }

        // 4. ERRO DE SINTAXE SQL
        if ($code === '42000' || str_contains($msg, 'syntax error')) {
            return "<strong>Erro de Sintaxe SQL:</strong> A instrução gerada pelo QueryBuilder ou SQL manual é inválida. <br><small>Sugestão: Verifique se não há palavras reservadas sendo usadas como nomes de colunas ou erros de aspas.</small>";
        }

        return "<strong>Erro de Banco de Dados:</strong> Ocorreu uma exceção de nível <code>PDOException</code>. Analise a mensagem acima para identificar falhas estruturais ou de dados.";
    }
}
