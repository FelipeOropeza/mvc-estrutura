<?php

declare(strict_types=1);

namespace Core\Http;

class Session
{
    public function start(): void
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            // Em ambientes Long-Running (como FrankenPHP), a sessão PHP nativa usa I/O block travando requests.
            // Para resolver, habilitamos nosso Custom Session Handler desprovido do native locked read/writes.
            $driver = function_exists('env') ? env('SESSION_DRIVER', 'file') : 'file';

            if ($driver === 'redis') {
                $host = function_exists('env') ? env('REDIS_HOST', '127.0.0.1') : '127.0.0.1';
                $port = function_exists('env') ? env('REDIS_PORT', 6379) : 6379;
                $password = function_exists('env') ? env('REDIS_PASSWORD', '') : '';

                $handler = new \Core\Http\Session\RedisSessionHandler($host, (int) $port, $password);
            } else {
                // Arquivo livre sem trava de concorrência massiva
                $path = __DIR__ . '/../../storage/framework/sessions';
                $handler = new \Core\Http\Session\FileSessionHandler($path);
            }

            // Avisa o PHP para usar nossaclasse como guardiã de $_SESSION
            session_set_save_handler($handler, true);

            // Garante que o PHP não faça garbage collection bagunçado nos discos se não for driver de arquivo nativo.
            if ($driver === 'redis') {
                ini_set('session.gc_probability', '0'); // O Redis lida com o TTL (setex) por conta própria
            }

            session_start();
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
}
