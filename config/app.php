<?php

return [
    /*
     |--------------------------------------------------------------------------
     | Application Paths
     |--------------------------------------------------------------------------
     |
     | Here you can specify the paths used by the framework to locate files.
     |
     */
    'paths' => [
        'controllers' => __DIR__ . '/../app/Controllers',
        'models' => __DIR__ . '/../app/Models',
        'middlewares' => __DIR__ . '/../app/Middleware',
        'views' => __DIR__ . '/../app/Views',
        'migrations' => __DIR__ . '/../database/migrations',

        // Caminho físico dos templates usados pelos comandos do Console
        'templates' => __DIR__ . '/../core/Console/Templates',
    ],

    /*
     |--------------------------------------------------------------------------
     | General Application Configuration
     |--------------------------------------------------------------------------
     |
     | Outras configurações gerais podem ir aqui (ex: nome, fuso horário, etc).
     |
     */
    'app' => [
        'name' => 'MVC Base Project',
        // Motores suportados: 'php' ou 'twig'
        'view_engine' => 'php',
        // Rota padrão do redirecionamento raiz, caso configurado
        'default_route' => function_exists('env') ? env('APP_DEFAULT_ROUTE', '/') : '/',
    ]
];
