# MVC Base em PHP Puro

Um esqueleto simples e leve para estrutura MVC em PHP, pronto para usar com Composer.

## Estrutura

- **app/**: Controllers, Models e Views da sua aplicação.
- **core/**: O núcleo do framework (Router, Controller base, etc).
- **public/**: Document root onde fica o `index.php`.
- **routes/**: Definição das rotas.

## Instalação Rápida

Para criar um novo projeto usando este esqueleto, execute o seguinte comando no seu terminal:

```bash
composer create-project felipe-etec/mvc-base nome-do-seu-projeto
```

Isso irá baixar a estrutura completa e instalar todas as dependências automaticamente.

## Como Rodar Localmente

Após instalar, entre na pasta e inicie o servidor embutido:

```bash
cd nome-do-seu-projeto
composer start
```

Acesse `http://localhost:8000`.

## CLI (Interface de Linha de Comando)

Este framework traz uma ferramenta CLI chamada **Forge** para ajudar a gerar arquivos rapidamente, assim como o `artisan` do Laravel.

Na raiz do seu projeto, rode:

```bash
forge make:controller UsuarioController
forge make:model Produto
forge make:view produto/lista
```

*(Nota para usuários Mac/Linux: use `php forge [comando]` se o script não for executável).*

## Criando Rotas

Edite `routes/web.php`:

```php
$router->get('/minha-rota', [MeuController::class, 'metodo']);
```

## Licença

MIT
