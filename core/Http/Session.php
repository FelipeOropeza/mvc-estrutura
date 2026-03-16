<?php

declare(strict_types=1);

namespace Core\Http;

class Session
{
    /**
     * Mantém uma referência ao handler para evitar Garbage Collection em Worker Mode
     */
    protected ?\SessionHandlerInterface $handler = null;

    public function start(): void
    {
        // Se já houver uma sessão ativa de uma request anterior (comum em Worker Mode do FrankenPHP), 
        // nós a fechamos para garantir que o session_start() seguinte carregue os dados REAIS do ID atual.
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
            $_SESSION = []; // Limpa o global para o próximo carregamento
        }

        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            $driver = function_exists('env') ? env('SESSION_DRIVER', 'file') : 'file';

            if ($driver === 'redis') {
                $host = function_exists('env') ? env('REDIS_HOST', '127.0.0.1') : '127.0.0.1';
                $port = function_exists('env') ? env('REDIS_PORT', 6379) : 6379;
                $password = function_exists('env') ? env('REDIS_PASSWORD', '') : '';

                try {
                    $this->handler = new \Core\Http\Session\RedisSessionHandler($host, (int) $port, $password);
                } catch (\Throwable $e) {
                    error_log("Redis Session Error: " . $e->getMessage() . " - Fallback para FileSessionHandler ativado.");
                    $driver = 'file'; 
                    $path = __DIR__ . '/../../storage/framework/sessions';
                    $this->handler = new \Core\Http\Session\FileSessionHandler($path);
                }
            } else {
                $path = __DIR__ . '/../../storage/framework/sessions';
                $this->handler = new \Core\Http\Session\FileSessionHandler($path);
            }

            // Hardening de Cookies de Sessão (OWASP)
            if (!headers_sent()) {
                session_set_cookie_params([
                    'lifetime' => (int) ini_get('session.gc_maxlifetime') ?: 7200,
                    'path' => '/',
                    'domain' => '',
                    'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
                    'httponly' => true,
                    'samesite' => 'Lax',
                ]);
            }

            session_set_save_handler($this->handler, true);

            if ($driver === 'redis') {
                ini_set('session.gc_probability', '0');
            }

            try {
                session_start();
            } catch (\Throwable $e) {
                // Se o session_start() falhar (comum se o handler travar), tentamos resetar para file
                error_log("Session Start Failure: " . $e->getMessage() . " - Forçando fallback para FileSession.");
                
                if (session_status() === PHP_SESSION_ACTIVE) {
                    session_write_close();
                }

                $path = __DIR__ . '/../../storage/framework/sessions';
                if (!is_dir($path)) mkdir($path, 0777, true);
                
                $this->handler = new \Core\Http\Session\FileSessionHandler($path);
                session_set_save_handler($this->handler, true);
                session_start();
            }
            
            // Log para debug de consistência em modo Worker
            $currentId = session_id();
            logger()->debug("Sessão Iniciada [ID: {$currentId}] Driver: {$driver}");
        }
    }

    public function all(): array
    {
        return $_SESSION ?? [];
    }

    public function get(string $key, mixed $default = null): mixed
    {
        if ($this->hasFlash($key)) {
            return $_SESSION['_flash'][$key];
        }

        return $_SESSION[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public function has(string $key): bool
    {
        return isset($_SESSION[$key]) || $this->hasFlash($key);
    }

    public function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public function destroy(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
            $_SESSION = [];
        }
    }

    public function flash(string $key, mixed $value): void
    {
        $_SESSION['_flash'][$key] = $value;
        $_SESSION['_flash_new'][] = $key;
    }

    private function hasFlash(string $key): bool
    {
        return isset($_SESSION['_flash'][$key]);
    }

    public function ageFlashData(): void
    {
        if (!isset($_SESSION['_flash'])) {
            $_SESSION['_flash'] = [];
        }

        // Limpa os flashes antigos (de 1 ciclo atrás)
        if (isset($_SESSION['_flash_old']) && is_array($_SESSION['_flash_old'])) {
            foreach ($_SESSION['_flash_old'] as $key) {
                unset($_SESSION['_flash'][$key]);
            }
        }

        // Os flashes definidos nesta requisição (_new) vão se tornar velhos (_old) para a próxima
        $_SESSION['_flash_old'] = $_SESSION['_flash_new'] ?? [];
        $_SESSION['_flash_new'] = [];
    }

    public function token(): string
    {
        if (!$this->has('_token')) {
            $this->set('_token', bin2hex(random_bytes(32)));
        }

        return (string) $this->get('_token');
    }

    /**
     * Regenera o ID da sessão para prevenir Session Fixation attacks.
     * SEMPRE chame isso após um login bem-sucedido.
     *
     * @param bool $deleteOld Se true, apaga os dados da sessão antiga (mais seguro)
     */
    public function regenerate(bool $deleteOld = true): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id($deleteOld);
            // Regenera também o CSRF token para invalidar o token da sessão anterior
            $this->set('_token', bin2hex(random_bytes(32)));
        }
    }
}
