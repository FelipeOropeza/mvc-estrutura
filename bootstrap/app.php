<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Criando a Aplicação
|--------------------------------------------------------------------------
|
| O primeiro passo é criar uma nova instância da Aplicação (Container).
| Ela serve como "cola" entre os componentes do framework e coordena o boot.
|
*/

$app = new \Core\Foundation\Application(realpath(__DIR__ . '/../'));

/*
|--------------------------------------------------------------------------
| Carregamento do Ambiente (.env)
|--------------------------------------------------------------------------
*/

if (class_exists(\Dotenv\Dotenv::class) && file_exists(__DIR__ . '/../.env')) {
    $dotenv = \Dotenv\Dotenv::createUnsafeImmutable(__DIR__ . '/../');
    $dotenv->safeLoad();
}

/*
|--------------------------------------------------------------------------
| Configuração Global de Erros (Handler)
|--------------------------------------------------------------------------
*/

$exceptionHandler = new \Core\Exceptions\Handler();
$exceptionHandler->register();

/*
|--------------------------------------------------------------------------
| Registrar e Iniciar Service Providers
|--------------------------------------------------------------------------
|
| Todo o Framework core (Database, Router) e os pacotes customizados
| do usuário são iniciados lendo as configurações.
|
*/

$app->registerConfiguredProviders();
$app->boot();

/*
|--------------------------------------------------------------------------
| Retornar o Objeto da Aplicação
|--------------------------------------------------------------------------
|
| Devolvemos o $app pronto para quem chamou (o index.php publico ou console CLI).
|
*/

return $app;
