# Injeção de Dependências e Service Providers

O motor MVC local é super alimentado, possuindo suporte a Inversão de Controle e Container Integrados. Isso significa que, em seus controllers e configurações, você nunca precisará mais construir `$conexao = new PDO...` na mão usando Singletons sujos pelo disco.

Para utilizar uma Conexão do seu Banco de Dados já instanciada magicamente pelo núcleo:
```php
$minhaVariavelGlobalSeguraEMagica = app(PDO::class);
```

## Service Providers
Localizados em `app/Providers/`. São as Centrais de Distribuição de Conhecimento para o Site iniciar de forma robusta e inteligente (Como o Lifecycle do Laravel) . Liste-os no `config/app.php` para o motor incluí-los na Partida Principal e Registre ali bibliotecas gigantes (`Stripe`, `Pagar.me`, `RedisServer`).
