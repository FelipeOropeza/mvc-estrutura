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
     */
    function response(): Response
    {
        static $response = null;
        if ($response === null) {
            $response = new Response();
        }
        return $response;
    }
}

if (!function_exists('request')) {
    /**
     * Helper global para a classe Request.
     */
    function request(): Request
    {
        static $request = null;
        if ($request === null) {
            $request = new Request();
        }
        return $request;
    }
}

if (!function_exists('view')) {
    /**
     * Helper global para renderizar uma View direto.
     */
    function view(string $viewName, array $data = []): mixed
    {
        $config = require __DIR__ . '/../../config/app.php';
        $viewPath = $config['paths']['views'];
        $engineType = $config['app']['view_engine'] ?? 'php';

        if ($engineType === 'twig') {
            $engine = new \Core\View\TwigEngine($viewPath);
        } else {
            $engine = new \Core\View\PhpEngine($viewPath);
        }

        return $engine->render($viewName, $data);
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

if (!function_exists('errors')) {
    /**
     * Recupera erros de validação da sessão (para usar nas Views).
     * Se passar o nome do campo (ex: 'email'), devolve só a string do erro daquele campo.
     */
    function errors(?string $field = null): mixed
    {
        $errors = $GLOBALS['flash_errors'] ?? [];
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
        $oldInputs = $GLOBALS['flash_old'] ?? [];
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
