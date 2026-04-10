# MVC Base — PHP Framework Full-Stack

> Micro-framework PHP puro, construído do zero, focado em performance, segurança e arquitetura moderna. Ideal para aprender como um framework funciona por dentro ou como base para aplicações reais.

[![PHP](https://img.shields.io/badge/PHP-8.1%2B-8892BF?logo=php)](https://php.net)
[![License](https://img.shields.io/badge/Licença-MIT-green)](LICENSE)
[![FrankenPHP](https://img.shields.io/badge/FrankenPHP-Worker%20Mode-orange)](https://frankenphp.dev)
[![Docker](https://img.shields.io/badge/Docker-Ready-2496ED?logo=docker)](https://docker.com)

---

## Por que este framework?

A maioria dos tutoriais de PHP ensina a usar o Laravel e esconde toda a mágica por baixo. Este projeto foi construído para **revelar essa mágica** — cada classe representa um conceito real de arquitetura:

- O `Router` implementa _pattern matching_ com regex e pipeline de middlewares
- O `Container` faz _autowiring_ via PHP Reflection API (como o Laravel)
- O `Model` implementa o padrão _Active Record_ com Eager Loading N+1-safe
- O `Kernel` segue o padrão _PSR-15_ de pipeline de requisição

---

## Features Principais

| Categoria | Feature |
|---|---|
| **HTTP** | Ciclo Request → Middleware Pipeline → Response (PSR-15 feeling) |
| **Roteamento** | Parâmetros dinâmicos, grupos, rotas nomeadas, PHP 8 Attributes (`#[Get]`, `#[Post]`) |
| **ORM** | Active Record, QueryBuilder fluente, Eager Loading, Soft Deletes, Transações |
| **Segurança** | CSRF nativo, headers de segurança, Mass Assignment guard, hash de senha |
| **Sessão** | Drivers File/Redis, flash messages, regeneração de ID pós-login |
| **Auth** | JWT para APIs stateless, suporte a scaffold com `php forge setup:auth` |
| **Validação** | PHP 8 Attributes (`#[Required]`, `#[Email]`, etc) nos DTOs, Mutators |
| **Views** | Motor PHP com layouts, sections e suporte a HTMX com `isHtmx()` |
| **Filas** | Jobs assíncronos com drivers Database e Redis |
| **Cache** | Drivers File e Redis com TTL |
| **Email** | PHPMailer com SMTP configurável |
| **Real-Time** | Mercure Hub integrado + helper `broadcast()` + `mercure_listen()` |
| **CLI (Forge)** | Scaffolding, migrações, processamento de filas |
| **Docker** | FrankenPHP + Mercure + Redis + MariaDB pré-configurados |
| **Debug** | Error handler visual com stack trace, diagnóstico de banco de dados |

---

## Instalação

### Método 1: Via Composer (Recomendado)

```bash
composer create-project felipe-code/mvc-base nome-do-seu-projeto
```

O instalador interativo irá configurar o `.env` e preparar o projeto automaticamente.

### Método 2: Via Git Clone

```bash
git clone https://github.com/FelipeOropeza/mvc-estrutura.git meu-app
cd meu-app
composer install
composer run post-create-project-cmd
```

### Método 3: Via Docker (FrankenPHP + Mercure)

```bash
docker-compose up -d --build
```

Acesse **http://localhost:8000**. O FrankenPHP roda em Worker Mode para máxima performance (milissegundos por requisição).

---

## Início Rápido (Servidor Local)

```bash
# Inicia o servidor embutido do PHP apontando para /public
composer start
```

Acesse **http://localhost:8000**.

---

## CLI — Forge

A ferramenta de linha de comando para scaffolding e manutenção:

```bash
# Criação de código
php forge make:controller NomeController
php forge make:controller Api/UserController --api
php forge make:model ProdutoModel
php forge make:view secao/minha-view
php forge make:component meu-componente
php forge make:migration CreateProdutosTable
php forge make:middleware VerificarAcessoMiddleware
php forge make:rule CpfValido
php forge make:mutator LimpaCpf

# Banco de dados
php forge migrate
php forge migrate:rollback
php forge migrate:fresh

# Scaffolding de sistemas completos
php forge setup:auth       # Sistema de autenticação completo
php forge setup:api        # Scaffold de API JWT
php forge setup:aviso      # Demo de avisos em tempo real (Mercure)

# Processamento de filas
php forge queue:work

# Otimização de produção
php forge optimize         # Cache de rotas para performance máxima
php forge optimize:clear   # Limpa o cache de rotas
```

---

## Estrutura de Diretórios

```
.
├── app/
│   ├── Controllers/     # Lógica HTTP
│   ├── Models/          # Active Record (estende Core\Database\Model)
│   ├── Middleware/      # Middlewares da aplicação
│   └── Providers/       # Service Providers
├── bootstrap/           # Inicialização do framework
├── config/              # Configurações (app, database, middleware, mail...)
├── core/                # O framework em si
│   ├── Auth/            # TokenManager (JWT)
│   ├── Cache/           # Drivers de cache (File, Redis)
│   ├── Database/        # Connection (PDO), Model, QueryBuilder
│   ├── Exceptions/      # Handler, HttpException, ValidationException
│   ├── Http/            # Request, Response, Kernel, Pipeline, Session
│   ├── Mail/            # MailManager, PHPMailer driver
│   ├── Queue/           # QueueManager, Job
│   ├── Routing/         # Router, AttributeRouteScanner, RouteCompiler
│   ├── Support/         # Container (IoC), Logger, helpers.php
│   ├── Validation/      # Validator, DataTransferObject
│   └── View/            # PhpEngine, EngineInterface
├── database/
│   └── migrations/      # Arquivos de migração
├── docs/                # Documentação completa
├── public/              # Document root (index.php, assets)
├── resources/views/     # Templates PHP
├── routes/              # web.php, api.php
├── storage/             # Logs, cache, sessões, uploads
├── forge                # CLI entry point (Linux/Mac)
└── forge.bat            # CLI entry point (Windows)
```

---

## Exemplo Rápido

**Rota + Controller + Model em 3 arquivos:**

```php
// routes/web.php
Route::get('/produtos', [ProdutoController::class, 'index'])->name('produtos.index');
Route::post('/produtos', [ProdutoController::class, 'store']);
```

```php
// app/Controllers/ProdutoController.php
class ProdutoController extends Controller
{
    public function index(): Response
    {
        $produtos = (new Produto())->orderBy('nome')->get();
        return view('produtos/index', compact('produtos'));
    }

    public function store(): Response
    {
        $data = validate(new ProdutoDto());
        (new Produto())->insert($data);
        return redirect(route('produtos.index'));
    }
}
```

```php
// app/Models/Produto.php
class Produto extends Model
{
    protected array $fillable = ['nome', 'preco', 'categoria_id'];
    protected array $hidden   = ['custo_interno'];

    public function categoria(): ?Categoria
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }
}
```

---

## Documentação Completa

Consulte a pasta `docs/` para guias detalhados:

➡ [Índice da Documentação](docs/framework.md)

| # | Guia |
|---|---|
| 02 | [Estrutura de Diretórios](docs/02-EstruturaDeDiretorios.md) |
| 03 | [Roteamento Avançado](docs/03-RoteamentoAvancado.md) |
| 04 | [Controllers e Services](docs/04-ControllersEServices.md) |
| 05 | [Banco de Dados e ORM](docs/05-BancoDeDados.md) |
| 06 | [Validações e DTOs](docs/06-ValidacoesEDtos.md) |
| 07 | [Mutations](docs/07-Mutations.md) |
| 08 | [Middlewares e Segurança](docs/08-MiddlewaresESeguranca.md) |
| 09 | [Upload de Arquivos](docs/09-UploadDeArquivos.md) |
| 10 | [Views e UI](docs/10-ViewsEUI.md) |
| 11 | [Injeção de Dependências](docs/11-InjecaoDeDependencias.md) |
| 12 | [CLI e Migrations](docs/12-CLIMigrations.md) |
| 13 | [Helpers Globais](docs/13-HelpersGlobais.md) |
| 14 | [Redis e Sessões](docs/14-RedisESessoes.md) |
| 15 | [Exceções e Debug](docs/15-ExcecoesEDebug.md) |
| 16 | [Nuvem e FrankenPHP](docs/16-NuvemEFrankenPHP.md) |
| 17 | [JWT e API](docs/17-JWT-E-API.md) |
| 18 | [E-mails](docs/18-Emails.md) |
| 19 | [Filas e Jobs](docs/19-Filas-E-Jobs.md) |
| 20 | [Cache](docs/20-Cache.md) |
| 21 | [Eventos em Tempo Real (Mercure)](docs/21-MercureRealTime.md) |
| 22 | [Tutorial CRUD Completo](docs/22-TutorialCRUD.md) |
| 23 | [Broadcasting Real-Time](docs/23-BroadcastingRealTime.md) |

---

## Exemplos em Destaque

- **[Avisos em Tempo Real](docs/REALTIME_DEMO.md)** — Notificações instantâneas com HTMX e Mercure em menos de 5 minutos.

---

## Requisitos

- PHP **8.1+** (recomendado 8.3+)
- Extensões: `pdo`, `pdo_mysql` (ou `pdo_pgsql` / `pdo_sqlite`), `mbstring`, `openssl`
- Composer 2.x

---

## Licença

[MIT](LICENSE) — Feito com propósito educacional e uso em produção.
