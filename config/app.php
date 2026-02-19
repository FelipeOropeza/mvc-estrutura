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
        'views' => __DIR__ . '/../app/Views',

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
    ]
];
