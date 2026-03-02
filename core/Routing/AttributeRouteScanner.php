<?php

declare(strict_types=1);

namespace Core\Routing;

use Core\Attributes\Route\Get;
use Core\Attributes\Route\Post;
use Core\Attributes\Route\Put;
use Core\Attributes\Route\Delete;
use Core\Attributes\Route\Patch;
use Core\Attributes\Route\Middleware;
use ReflectionClass;

class AttributeRouteScanner
{
    /**
     * @var array<class-string, string>
     */
    private array $supportedAttributes = [
        Get::class => 'GET',
        Post::class => 'POST',
        Put::class => 'PUT',
        Delete::class => 'DELETE',
        Patch::class => 'PATCH',
    ];

    public function scan(Router $router, string $directory, string $baseNamespace): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory));
        $regex = new \RegexIterator($iterator, '/^.+\.php$/i', \RecursiveRegexIterator::GET_MATCH);

        foreach ($regex as $file) {
            $filePath = $file[0];
            $relativePath = str_replace([$directory, '.php', '/'], ['', '', '\\'], $filePath);
            $className = $baseNamespace . ltrim($relativePath, '\\');

            if (!class_exists($className)) {
                // Tenta fazer um require caso não tenha sido autoloaded ainda (raro mas seguro)
                require_once $filePath;
                if (!class_exists($className)) {
                    continue;
                }
            }

            $this->scanClass($router, $className);
        }
    }

    private function scanClass(Router $router, string $className): void
    {
        $reflectionClass = new ReflectionClass($className);

        foreach ($reflectionClass->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {

            $routeAdded = false;

            foreach ($this->supportedAttributes as $attributeClass => $httpMethod) {
                $attributes = $method->getAttributes($attributeClass);

                foreach ($attributes as $attribute) {
                    $routeArgs = $attribute->getArguments();
                    $uri = isset($routeArgs[0]) ? strval($routeArgs[0]) : '/';

                    // Registra a rota
                    $router->{$httpMethod === 'GET' ? 'get' : strtolower($httpMethod)}($uri, [$className, $method->getName()]);
                    $routeAdded = true;
                }
            }

            // Se a rota foi adicionada por este método, pega os atributos de Middleware
            if ($routeAdded) {
                $middlewareAttributes = $method->getAttributes(Middleware::class);
                foreach ($middlewareAttributes as $mwAttribute) {
                    $mwArgs = $mwAttribute->getArguments();
                    if (isset($mwArgs[0])) {
                        $classes = is_array($mwArgs[0]) ? $mwArgs[0] : [$mwArgs[0]];
                        // Usamos o método do router que acopla na "última rota adicionada"
                        $router->middleware($classes);
                    }
                }
            }
        }
    }
}
