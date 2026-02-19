# MVC Base em PHP Puro

Um esqueleto simples e leve para estrutura MVC em PHP, pronto para usar com Composer.

## Estrutura

- **app/**: Controllers, Models e Views da sua aplicação.
- **core/**: O núcleo do framework (Router, Controller base, etc).
- **public/**: Document root onde fica o `index.php`.
- **routes/**: Definição das rotas.

## Como Usar

1.  Clone este repositório.
2.  Execute `composer install` para gerar o autoloader.
3.  Configure seu servidor web (Apache/Nginx) para apontar para a pasta `public/`.
4.  Acesse `http://localhost/seu-projeto/public/` (ou configure um VirtualHost).

## Criando Rotas

Edite `routes/web.php`:

```php
$router->get('/minha-rota', [MeuController::class, 'metodo']);
```

## Publicando no Packagist

1.  Hospede este código no GitHub.
2.  Crie uma conta no [Packagist.org](https://packagist.org/).
3.  Submeta a URL do seu repositório GitHub.
4.  Configure o Webhook para atualizações automáticas.

## Licença

MIT
