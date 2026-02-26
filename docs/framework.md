# Documentação do Framework MVC Base

Um micro-framework PHP profissional, extremamente rápido, desenhado com arquitetura de diretórios sólida (similar a Laravel/Symfony) e focado em produtividade através de validação nativa, Service Providers (Lifecycles), Container de Injeção de Dependências (IoC) e ferramentas CLI.

---

## 1. Estrutura de Diretórios

- **app/Controllers**: Aqui ficam seus Controladores, responsáveis por receber as requisições HTTP (`Core\Http\Request`) e orquestrar a lógica devolvendo uma `Response`.
- **app/Models**: Classes que representam as tabelas do seu banco de dados. Atuam como "Active Record", carregando inclusive as regras de validação via Atributos (PHP 8).
- **app/Providers**: Onde moram os "Plugins" customizados da sua aplicação (`AppServiceProvider`). É o local para você configurar dependências e serviços antes das Rotas rodarem.
- **app/Views**: Os arquivos de interface que serão renderizados para o usuário. Suporta PHP nativo ou Twig Engine.
- **bootstrap**: Contém o arquivo `app.php` responsável por inicializar o Container de Serviços e registrar a ponte do backend.
- **config**: Contém as configurações principais do aplicativo (`app.php`, `database.php`), incluindo a lista principal de **Providers** que devem ser ligados.
- **core**: O motor interno do Framework separado por pacotes lógicos (Router, Http, Foundation, Providers, Exceptions, Support). É blindado via `.htaccess` para não vazar acesso na web!
- **public**: O único diretório seguro para acesso direto via Web. Ele aponta para o `index.php` minimalista que engatilha o Framework de forma limpa.
- **storage/logs**: Onde seus logs internos (`app.log`) são escritos de forma segura quando há erros invisíveis em produção num deploy silencioso (`APP_DEBUG=false`).
- **routes**: As definições de URLs da sua aplicação (`web.php`).
- **server.php**: Roteador interno para desenvolvimento via `php -S localhost:8000 server.php` simulando a blindagem de proteção de pastas.

---

## 2. Injeção de Dependências (IoC) e Service Providers

O coração do framework deixa de ser procedimental e agora atua 100% como Orientado à Objetos modular via "Service Locator" e Injeção de Dependência. 

### Usando o Container
O Framework mantém todas as classes rodando num "Container" mestre. O código inteiro compartilha instâncias sem a necessidade de reescrever `new Objeto()` no meio da Request. Através do helper global `app()`, você interage com o coração:

```php
$conexao = app(Core\Database\Connection::class); // Já instanciado e seguro
```

### O Lifecyle (Service Providers)
No `config/app.php`, você registra seus Providers (Plugins). Imagine que você queira adicionar um sistema de Mailer:
Você criaria em `app/Providers/MailServiceProvider.php` o arquivo com dois pilares:
1. `register()`: Diz ao `$this->app->singleton(...)` como instanciar seu Serviço de E-mails conectando Senhas do `.env`.
2. `boot()`: Roda assim que tudo estiver registrado. Usado para inicialização base (como por exemplo incluir as Rotas).

---

## 3. Ferramenta CLI (Forge)

Assim como frameworks maiores possuem o Artisan ou o Symfony Console, este motor lida com arquiteturas usando a linha de comando local **Forge** presente na raiz do seu projeto.

Para utilizar no terminal do seu projeto:
```bash
# Criação de Arquivos (Boilerplates)
php forge make:controller UsuarioController
php forge make:model Produto
php forge make:view produto/lista
php forge make:middleware AuthMiddleware
```

E para mudar seu motor base para **Twig Engine** ao invés de PHP Puro de forma automatizada:
```bash
php forge setup:engine twig
```

---

## 4. Middlewares e Proteção de Rotas (PSR-15 Style)

O framework possui um sistema de pipeline de Middlewares (filtros de requisição). Totalmente **Stateless**, os middlewares recebem uma `Request` e devem obrigatoriamente retornar uma `Response` (Ao invés do perigoso e matador `exit`), preparando o terreno perfeitamente para Servidores Assíncronos como o FrankenPHP ou AWS Lambda Bref.

```php
// routes/web.php
use App\Middleware\AuthMiddleware;

$router->get('/painel-secreto', [AdminController::class, 'index'])
       ->middleware(AuthMiddleware::class);
```

Dentro do Middleware, se a verificação falhar:

```php
public function handle(Request $request, Closure $next)
{
    if (!$request->get('token')) {
        // Interrompe e devolve um Objeto Response pronto! O Pipeline do Kernel repassará até o Index sem "quebrar" o servidor.
        return \Core\Http\Response::makeJson(['erro' => 'Não autorizado'], 401); 
    }

    $request->attributes['usuarioLogado'] = 'Felipe Admin'; 

    return $next($request);
}
```

---

## 5. Validação Atributiva Dinâmica (PHP 8)

Uma das maiores inovações deste micro-framework é o seu sistema de Validação embutido diretamente nas **Models**, sem precisar escrever centenas de IFs.

```php
namespace App\Models;

use Core\Database\Model;
use Core\Attributes\Required;
use Core\Attributes\Email;

class User extends Model
{
    protected string $table = 'users';

    #[Required('Por favor, preencha o seu nome.')]
    public ?string $nome = null;

    #[Required('Campo de e-mail obrigatório')]
    #[Email('E-mail com formato inválido.')]
    public ?string $email = null;
}
```

Para acionar a checagem que devolve um array sanitizado ou injeta os Erros Flash de Sessão automaticamente voltando a rota anterior:
```php
public function store()
{
    $user = new \App\Models\User();
    $dadosSeguros = $user->validate();
    $user->insert($dadosSeguros);

    return \Core\Http\Response::makeRedirect('/sucesso');
}
```

### Recuperando Erros e Old Inputs na UI:
Usando PHP Engine (`public/index.php` blindará as tags XSS automaticamente pra sua segurança):
```php
<input type="text" name="nome" value="<?= old('nome') ?>">
<span class="erro_vermelho"><?= errors('nome') ?></span>
```

---

## 6. Helpers Globais Úteis

Estas funções vivem mapeadas internamente em `Core\Support\helpers.php` e agilizam muita funcionalidade em qualquer pedaço do ecossistema:

- `app()`: Acessa o Container Principal (`Application`). Exemplo: `app()->get(Model::class)`.
- `logger()`: Grava uma mensagem no arquivo oculto `storage/logs/app.log` usando `logger()->info('Usuário conectou')`.
- `request()`: Acessa os dados atuais (`$_POST`, `$_GET`, etc.) sanitizados através deste DTO global sem estado global sujo.
- `response()`: Cria o DTO de Response HTTP com cabeçalhos apropriados.
- `view('nome_arquivo', [])`: Renderiza um HTML final ou view mapeada da pasta `app/Views/`.
- `old('campo', 'padrao')` e `errors('campo')`: Recuperadores vitais de Sessão Flash para UI de Formulários.
