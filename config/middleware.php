<?php

return [
    /*
     |--------------------------------------------------------------------------
     | Global Middlewares
     |--------------------------------------------------------------------------
     |
     | Estes middlewares rodam em TODAS as requisições HTTP da aplicação,
     | independente da rota. Útil para sessões, CORS, manutenções, etc.
     |
     */
    'global' => [
        \Core\Http\Middleware\StartSession::class,
        // \App\Middleware\HandleCors::class,
    ],

    /*
     |--------------------------------------------------------------------------
     | Middleware Groups
     |--------------------------------------------------------------------------
     |
     | Grupos de Middlewares permitem empacotar vários middlewares sob um 
     | único apelido. Útil para separar lógicas web vs api.
     | Exemplo: ->middleware('web')
     |
     */
    'groups' => [
        'web' => [
            // \Core\Http\Middleware\VerifyCsrfToken::class,
        ],
        'api' => [
            // \App\Middleware\ThrottleRequests::class,
        ],
    ],

    /*
     |--------------------------------------------------------------------------
     | Route Middleware Aliases
     |--------------------------------------------------------------------------
     |
     | Aqui você pode dar apelidos pros seus middlewares, pra ficar mais bonito
     | nas suas rotas. Exemplo: ->middleware('auth')
     |
     */
    'aliases' => [
        // 'auth' => \App\Middleware\AuthMiddleware::class,
        // 'guest' => \App\Middleware\RedirectIfAuthenticated::class,
    ],
];
