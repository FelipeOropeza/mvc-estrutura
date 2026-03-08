<?php

declare(strict_types=1);

use Core\Http\Request;
use Core\Http\Response;

if (!function_exists('logger')) {
    /**
     * Instancia o Logger do sistema e facilita gravação de arquivos de debug
     */
    function logger(): \Core\Support\Logger
    {
        static $logger = null;
        if ($logger === null) {
            $logger = new \Core\Support\Logger();
        }
        return $logger;
    }
}

if (!function_exists('app')) {
    /**
     * Helper global para o Container de Injeção de Dependências.
     * Resolve uma classe do container ou retorna a instância do próprio Container.
     */
    function app(?string $abstract = null): mixed
    {
        $container = \Core\Support\Container::getInstance();

        if ($abstract === null) {
            return $container;
        }

        return $container->get($abstract);
    }
}

if (!function_exists('response')) {
    /**
     * Helper global para a classe Response.
     * IMPORTANTE: Sempre retorna uma nova instância — nunca reutiliza estado entre requisições.
     * Essencial para modo Worker (FrankenPHP) onde o processo não morre entre requests.
     */
    function response(string $content = '', int $status = 200, array $headers = []): Response
    {
        return new Response($content, $status, $headers);
    }
}

if (!function_exists('request')) {
    /**
     * Helper global para a classe Request.
     */
    function request(): Request
    {
        // No Worker Mode, a Request é rotativa. Buscamos sempre a instância mais fresca do Container
        return app(Request::class);
    }
}

if (!function_exists('view')) {
    /**
     * Helper global para renderizar uma View direto.
     */
    function view(string $viewName, array $data = []): mixed
    {
        return app(\Core\View\EngineInterface::class)->render($viewName, $data);
    }
}

if (!function_exists('validate')) {
    /**
     * Usa PHP 8 Attributes para validar os dados do Request baseados em um DTO (Objeto).
     * 
     * @param object $dto O Objeto de Transferencia (ex: UserCreateRequest)
     * @return array Retorna os dados válidados ou exibe a falha como JSON de forma automatizada(422)
     */
    function validate(object $dto): array
    {
        $validator = new \Core\Validation\Validator();
        // Pegamos todos os parametros (Seja POST/GET/JSON) e tentamos "encaixar" no DTO
        $isValid = $validator->validate($dto, request()->all());

        if (!$isValid) {
            $errors = $validator->getErrors();
            throw new \Core\Exceptions\ValidationException($errors, request()->all());
        }

        return $validator->getValidatedData();
    }
}

if (!function_exists('fail_validation')) {
    /**
     * Lança manualmente um erro de validação parando a requisição e retornando com os inputs preenchidos (old).
     * 
     * @param string|array $field Nome do campo (string) ou array de erros completo ['campo' => 'erro']
     * @param string|null $message Mensagem de erro caso o $field seja apenas uma string
     * @throws \Core\Exceptions\ValidationException
     */
    function fail_validation(string|array $field, ?string $message = null): void
    {
        $errors = is_array($field) ? $field : [$field => [$message]];

        // Garante a formatação internal do ValidationException que espera arrays de strings
        foreach ($errors as $k => $v) {
            if (!is_array($v)) {
                $errors[$k] = [$v];
            }
        }

        throw new \Core\Exceptions\ValidationException($errors, request()->all());
    }
}

if (!function_exists('errors')) {
    /**
     * Recupera erros de validação da sessão (para usar nas Views).
     * Se passar o nome do campo (ex: 'email'), devolve só a string do erro daquele campo.
     */
    function errors(?string $field = null): mixed
    {
        $errors = session('_flash_errors', []);
        if ($field) {
            // Em caso de array (vários erros num campo), pegamos o primeiro pra facilitar a view
            $fieldErrors = $errors[$field] ?? [];
            return is_array($fieldErrors) && !empty($fieldErrors) ? $fieldErrors[0] : null;
        }
        return $errors;
    }
}

if (!function_exists('old')) {
    /**
     * Mantém o valor preenchido no formulário caso tenha dado erro de validação.
     */
    function old(string $field, mixed $default = ''): mixed
    {
        $oldInputs = session('_flash_old', []);
        return $oldInputs[$field] ?? $default;
    }
}

if (!function_exists('env')) {
    /**
     * Recupera uma variável de ambiente ou retorna um valor padrão.
     */
    function env(string $key, mixed $default = null): mixed
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? null;

        if ($value === null) {
            return $default;
        }

        switch (strtolower((string) $value)) {
            case 'true':
            case '(true)':
                return true;
            case 'false':
            case '(false)':
                return false;
            case 'empty':
            case '(empty)':
                return '';
            case 'null':
            case '(null)':
                return null;
        }

        return $value;
    }
}

if (!function_exists('route')) {
    /**
     * Gera uma URL para uma rota nomeada.
     * 
     * @param string $name O nome da rota (ex: 'user.show')
     * @param array $params Parâmetros dinâmicos da rota (ex: ['id' => 3])
     * @return string A URL completa a ser impressa no HTML
     */
    function route(string $name, array $params = []): string
    {
        $router = \Core\Routing\Router::getInstance();
        if ($router) {
            try {
                return $router->generateUrl($name, $params);
            } catch (\Exception $e) {
                // Em produção, isso pode ser logado e retornar "#" ou lançar até que seja arrumado
                return '#route-not-found-' . $name;
            }
        }
        return '';
    }
}

if (!function_exists('session')) {
    /**
     * Helper para interagir com a Sessão global.
     * Retorna a instância se não passar key.
     */
    function session(?string $key = null, mixed $default = null): mixed
    {
        $session = request()->session();
        if (!$session) {
            // Em cenários isolados sem request injetado, buscaríamos do Container.
            $session = app(\Core\Http\Session::class);
        }

        if ($key === null) {
            return $session;
        }

        return $session->get($key, $default);
    }
}

if (!function_exists('csrf_token')) {
    /**
     * Retorna o token CSRF atual.
     */
    function csrf_token(): string
    {
        return session()->token();
    }
}

if (!function_exists('csrf_field')) {
    /**
     * Retorna o input hidden HTML pronto com o token CSRF.
     */
    function csrf_field(): string
    {
        return '<input type="hidden" name="_token" value="' . csrf_token() . '">';
    }
}

if (!function_exists('storage_url')) {
    /**
     * Gera a URL pública para um arquivo armazenado na pasta storage.
     * Ex: storage_url('produtos/foto.jpg') -> '/storage/produtos/foto.jpg'
     */
    function storage_url(?string $path): string
    {
        if (!$path) {
            return '';
        }

        // Se o path já for uma URL completa, retorna ela mesma
        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        return '/storage/' . ltrim($path, '/');
    }
}

if (!function_exists('e')) {
    /**
     * Escapa caracteres especiais HTML para exibição segura nas Views.
     * SEMPRE use este helper ao imprimir dados do usuário: <?= e($variavel) ?>
     * Não use no Validator (que é camada de entrada) — só na saída/View.
     */
    function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('abort')) {
    /**
     * Lança uma exceção HTTP para interromper a request com um código de status.
     * Ex: abort(404), abort(403, 'Sem permissão')
     *
     * @param  int    $code    Código HTTP (403, 404, 500, etc.)
     * @param  string $message Mensagem opcional de erro
     * @throws \Core\Exceptions\HttpException Sempre lançada — esta função nunca retorna normalmente.
     */
    function abort(int $code, string $message = ''): void
    {
        $defaultMessages = [
            400 => 'Bad Request',
            401 => 'Não autenticado.',
            403 => 'Acesso negado.',
            404 => 'Não encontrado.',
            405 => 'Método não permitido.',
            422 => 'Entidade não processável.',
            429 => 'Muitas requisições.',
            500 => 'Erro interno do servidor.',
            503 => 'Serviço indisponível.',
        ];

        $msg = $message ?: ($defaultMessages[$code] ?? 'Erro.');

        throw new \Core\Exceptions\HttpException($msg, $code);
    }
}

if (!function_exists('redirect')) {
    /**
     * Retorna uma Response de redirecionamento imediato.
     * Ex: return redirect('/login'); return redirect(route('home'));
     */
    function redirect(string $url, int $status = 302): Response
    {
        return Response::makeRedirect($url, $status);
    }
}
