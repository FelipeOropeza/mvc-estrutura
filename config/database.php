<?php

// Tenta carregar variáveis do .env usando a biblioteca do Laravel (vlucas/phpdotenv)
// que será instalada durante o setup. Se não estiver instalada (erro silencioso),
// o getDefault fallback cuidará disso.

return [
    /*
     |--------------------------------------------------------------------------
     | Default Database Connection Name
     |--------------------------------------------------------------------------
     |
     | Se a variável DB_CONNECTION do .env estiver ausente, ou se a 
     | lib dotenv não foi carregada, usaremos o 'mysql'.
     |
     */
    'default' => getenv('DB_CONNECTION') ?: 'mysql',

    /*
     |--------------------------------------------------------------------------
     | Database Connections
     |--------------------------------------------------------------------------
     |
     */
    'connections' => [

        'mysql' => [
            'driver' => 'mysql',
            'host' => getenv('DB_HOST') ?: '127.0.0.1',
            'port' => getenv('DB_PORT') ?: '3306',
            'database' => getenv('DB_DATABASE') ?: 'mvc_base',
            'username' => getenv('DB_USERNAME') ?: 'root',
            'password' => getenv('DB_PASSWORD') ?: '',
            'charset' => 'utf8mb4',
        ],

        'pgsql' => [
            'driver' => 'pgsql',
            'host' => getenv('DB_HOST') ?: '127.0.0.1',
            'port' => getenv('DB_PORT') ?: '5432',
            'database' => getenv('DB_DATABASE') ?: 'mvc_base',
            'username' => getenv('DB_USERNAME') ?: 'postgres',
            'password' => getenv('DB_PASSWORD') ?: '',
            'charset' => 'utf8',
        ],

    ],
];
