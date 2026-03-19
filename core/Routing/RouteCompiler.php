<?php

declare(strict_types=1);

namespace Core\Routing;

use Closure;
use ReflectionClass;
use ReflectionNamedType;

class RouteCompiler
{
    public function compile(Router $router): string
    {
        $routes = $router->getRoutes();
        $code = "<?php\n\n// Arquivo gerado automaticamente pelo `php forge optimize`.\n// Nao edite manualmente!\n\nreturn [\n";

        foreach ($routes as $method => $patterns) {
            $code .= "    '$method' => [\n";
            foreach ($patterns as $pattern => $info) {
                // Escape aspas simples no pattern para não quebrar a construção do array PHP
                $safePattern = str_replace("'", "\'", $pattern);
                $code .= "        '$safePattern' => [\n";

                // Middlewares
                $middlewares = $info['middlewares'] ?? [];
                $middlewaresCode = "[\n";
                foreach ($middlewares as $mw) {
                    if (is_string($mw)) {
                        $middlewaresCode .= "                '{$mw}',\n";
                    }
                }
                $middlewaresCode .= "            ]";
                $code .= "            'middlewares' => $middlewaresCode,\n";

                // Action e Dependências (Reflection Recursivo no momento do Build!)
                $action = $info['action'];
                if (is_array($action) && count($action) === 2 && is_string($action[0])) {
                    $class = $action[0];
                    $methodName = $action[1];

                    $factoryCode = $this->buildInstantiationCode($class);

                    $code .= "            'action' => [\n";
                    $code .= "                'class' => '$class',\n";
                    $code .= "                'method' => '$methodName',\n";
                    $code .= "                'factory' => function() {\n";
                    $code .= "                    return $factoryCode;\n";
                    $code .= "                }\n";
                    $code .= "            ]\n";
                } elseif ($action instanceof Closure) {
                    $code .= "            'action' => null // Rotas closure nao sofrem cache anonimo.\n";
                } else {
                    $code .= "            'action' => null\n";
                }

                $code .= "        ],\n";
            }
            $code .= "    ],\n";
        }
        $code .= "];\n";

        return $code;
    }

    private function buildInstantiationCode(string $class): string
    {
        // Delega para o Container em Runtime sempre, evitando problemas com app() e bindings dinâmicos
        return "\\Core\\Support\\Container::getInstance()->get('\\$class')";
    }
}
