# MVC Base Project (Micro Framework)

Um esqueleto PHP puro, ultra-leve e focado em performance (Stateless). Construído do zero para suportar a Arquitetura Moderna do PHP: Container de Injeção de Dependências (PSR-11 feeling), Cycle de Request/Response via Middlewares (PSR-15 feeling), Service Providers e preparado para servidores assíncronos como o FrankenPHP.

## Principais Features Atuais

* **Arquitetura Stateless**: Sem vazamentos globais (Globals como `$_GET` e `$_POST` são embalados no objeto `Request`). 
* **Container de Injeção de Dependências (IoC)**: Autowiring de classes inteligentes via Reflection API. Funções Globais como `app()` e `logger()`.
* **Sessões e Proteção CSRF Nativa**: Gerenciamento de sessão Orientado a Objetos integrado ao pipeline (Middlewares) e proteção fácil de formulários contra ataques Cross-Site Request Forgery.
* **Router Expressivo e Rápido**: Suporte a parâmetros dinâmicos na URL, **Grupos de Rotas** (com prefixos e middlewares aninhados) e Namespaces limpos.
* **Docker & FrankenPHP Integrado**: Ambiente pronto para a nuvem (Render, AWS) e desenvolvedores. Utiliza a imagem oficial do FrankenPHP baseada em Debian operando com o cobiçado **Worker Mode** para performance extrema na casa dos milissegundos.
* **Service Providers Lifecycle**: Motor flexível similar ao Laravel, permitindo construção modular de recursos através de classes simples no `config/app.php`.
* **Database Avançado (ORM)**: Modelos trazem um `QueryBuilder` embutido para queries complexas fluidas, Suporte a Relacionamentos (`belongsTo`, `hasMany`), e Gatilhos de Mutação e Validação acoplados.
* **Upload Seguro e Storage**: Abstração Orientada a Objetos robusta para manipulação e validação de `UploadedFile`.
* **Segurança e Log de Falhas**: Exceções são silenciadas no arquivo `storage/logs/app.log` se o modo de debug estiver inativo (`APP_DEBUG=false`), blindando a visão do usuário final num Deploy de Produção.
* **CLI (Forge)**: Uma ferramenta de console robusta e extensível para criar código pré-fabricado, regras customizadas e rodar migrações.

## Documentação

Para mergulhar fundo e aprender a separar a lógica da sua aplicação de forma profissional num MVC, construir modelos, usar o Validator baseado em PHP 8 Attributes e a CLI do Framework, consulte a documentação dedicada na pasta `docs/`:

=> [Ler a Documentação do Motor MVC](docs/framework.md)

---

## Início Rápido (Instalação e Teste)

### Método 1: Via Composer (Recomendado)
A forma mais fácil de criar a aplicação é rodar o `create-project`. Ele baixará a última versão, iniciará o **instalador interativo** e limpará os arquivos de instalação ao finalizar.

```bash
composer create-project felipe-code/mvc-base nome-do-seu-projeto
```

### Método 2: Via Git Clone Manual
Se preferir clonar o repositório, você pode engatilhar o instalador interativo logo em seguida com os comandos abaixo:

```bash
git clone https://github.com/FelipeOropeza/mvc-estrutura.git meu-app
cd meu-app
composer install
composer run post-create-project-cmd
```

### Iniciando o Servidor Local Seguro:
Uma vez que o projeto esteja instanciado, inicie o servidor interno:
```bash
composer start
```
*(O script `start` apontará para `server.php` garantindo que testes locais reproduzem o travamento de segurança da pasta `/public` com eficiência absoluta).*

Acesse `http://localhost:8000` no seu navegador.

---

### Comandos Rápidos da CLI (Forge):
```bash
php forge make:controller NomeController
php forge make:model TabelaModel
php forge make:view secao/nova-view
php forge make:migration CreateUsersTable
php forge make:middleware VerificarAcessoMiddleware
php forge make:rule CpfValido
php forge make:mutator LimpaCpf
php forge migrate
php forge setup:engine twig
```

## Licença

MIT
