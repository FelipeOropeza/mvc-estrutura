# Documentação do Framework MVC Base

Um micro-framework PHP profissional, extremamente rápido, desenhado com arquitetura de diretórios sólida (similar a Laravel/Symfony) e focado em produtividade através de validação nativa e ferramentas CLI.

---

## 1. Estrutura de Diretórios

- **app/Controllers**: Aqui ficam seus Controladores, responsáveis por receber as requisições HTTP (`Core\Http\Controller`) e orquestrar a lógica.
- **app/Models**: Classes que representam as tabelas do seu banco de dados. Agora elas atuam como "Active Record", carregando inclusive as regras de validação dos dados daquela tabela.
- **app/Views**: Os arquivos de interface que serão renderizados para o usuário. Suporta PHP nativo ou Twig Engine, dependo da sua escolha na inicialização.
- **config**: Contém as configurações principais do aplicativo (como arquivos de banco de dados e qual motor de view utilizar).
- **core**: O motor interno do Framework separado por pacotes lógicos:
  - `core/Http`: Lida de Request, Response e o controlador base.
  - `core/Routing`: O Roteador principal (`Router.php`).
  - `core/Support`: Ferramentas globais (helpers) para te acelerar.
  - `core/Validation` & `Attributes`: Lógica da arquitetura de validação focada no PHP 8.
- **public**: O ponto de entrada da aplicação (`index.php`), e o local ideal para expor de arquivos CSS, JS e Imagens públicas.
- **routes**: As definições de URLs da sua aplicação (`web.php`).

---

## 2. Ferramenta CLI (Forge)

Assim como frameworks maiores possuem o Artisan ou o Symfony Console, este motor lida com arquiteturas usando a linha de comando local **Forge** presente na raiz do seu projeto.

Para utilizar no terminal do seu projeto:
```bash
# Criação de Arquivos (Boilerplates)
php forge make:controller UsuarioController
php forge make:model Produto
php forge make:view produto/lista
php forge make:middleware AuthMiddleware
```

### Setup Inteligente de Views
O Forge lê o seu arquivo `config/app.php`. Toda vez que você roda `make:view`, ele descobre automaticamente se você está usando a extensão `.php` ou a extensão `.twig` e cria o arquivo correto instantaneamente!

Se você se arrependeu e quer mudar o seu motor de template padrão no meio do desenvolvimento de forma rápida (ou limpá-lo no início):
```bash
php forge setup:engine twig
# ou 
php forge setup:engine php
```
*(Ele automaticamente instalará a biblioteca via composer e apagará visualizações antigas de exemplo que entrarem em conflito).*

---

## 3. Middlewares e Proteção de Rotas

O framework possui um sistema robusto de Middlewares (filtros de requisição) com suporte à injeção de atributos e bloqueios. Após criar seu Middleware com o comando no CLI (ex: `php forge make:middleware AuthMiddleware`), você o associa as rotas para protege-las:

```php
// routes/web.php
use App\Middleware\AuthMiddleware;

$router->get('/painel-secreto', [AdminController::class, 'index'])
       ->middleware(AuthMiddleware::class);
```

Dentro do Middleware, você pode barrar a continuação:

```php
public function handle(Request $request, Closure $next)
{
    if (!$request->get('token')) {
        // Interrompe e não deixa chegar ao Controller!
        response()->json(['erro' => 'Não autorizado'], 401); 
        exit;
    }

    // Injeta dados processados pra Rota usar depois
    $request->attributes['usuarioLogado'] = 'Felipe Admin'; 

    return $next($request);
}
```

---

## 4. Validação Atributiva Dinâmica (PHP 8)

Uma das maiores inovações deste micro-framework é o seu sistema de Validação embutido diretamente nas **Models**, sem precisar escrever centenas de IFs.

### Declarando os Atributos no Model
Nós transformamos as colunas da sua tabela num "contrato", adicionado propriedades nela com a etiqueta dos Atributos de Validação embutidos do Core:

```php
namespace App\Models;

use Core\Database\Model;
use Core\Attributes\Required;
use Core\Attributes\Email;
use Core\Attributes\MinLength;

class User extends Model
{
    protected string $table = 'users';

    #[Required('Por favor, preencha o seu nome.')]
    #[MinLength(3, 'O nome precisa ter pelo menos 3 letras.')]
    public ?string $nome = null;

    #[Required('Campo de e-mail obrigatório')]
    #[Email('E-mail com formato inválido.')]
    public ?string $email = null;

    #[Required]
    #[MinLength(8, 'Sua senha deve ter no mínimo 8 caracteres.')]
    public ?string $senha = null;
}
```

### Acionando a Validação no Controller
Para salvar os dados que vieram do formulário (o `$_POST`), seu `Controller` só precisa acionar o salvador. Esqueça ter que interceptá-los se eles forem inválidos; o framework fará um **Intercept & Redirect Automático** se o `validate()` falhar!

```php
public function store()
{
    $user = new \App\Models\User();

    // 1. Ele lê a $_POST automaticamente.
    // 2. Se falhar: Encerra, salva os erros na sessão flash e Volta pro formulário sozinho.
    // 3. Se ter sucesso: Devolve o Array completamente limpo.
    $dadosSeguros = $user->validate();

    // 4. Salva no banco de um jeito absurdamente enxuto usando a rotina base.
    $user->insert($dadosSeguros);

    return response()->redirect('/sucesso');
}
```

### Recuperando os erros gráficos na sua View Html/Twig
Se a validação falhou e ele jogou o usuário de volta para a tela, injete os comandos visuais do helper global para ajudá-los:

Usando PHP Engine:
```php
<!-- old() segura o que o cara tinha digitado, pra ele nao ter q re-digitar. -->
<input type="text" name="nome" value="<?= old('nome') ?>">
<!-- errors() coleta a exata mensagem que você cadastrou no Atributo da Model. -->
<span class="erro_vermelho"><?= errors('nome') ?></span>
```

Usando Twig Engine:
```twig
<!-- Nos motores twig nativos os helpers globais estão em modo nativo também! -->
<input type="text" name="nome" value="{{ old('nome') }}">
<span class="erro_vermelho">{{ errors('nome') }}</span>
```

---

## 4. Helpers Globais Úteis
Estas funções vivem mapeadas internamente em `Core\Support\helpers.php` e agilizam muita funcionalidade em qualquer pedaço do ecossistema:

- `request()`: Acessa a classe responsável pela Requisição atual e seus parametros daquele segundo.
- `response()`: Usada para gerenciar os redirecionamentos ou envios de cabeçalhos json rest (`response()->json($dadosArray, 200)`).
- `view('nome_arquivo', [])`: Renderiza um HTML final ou view mapeada da pasta `app/Views/` imediatamente passando as variáveis em um array seguro.
- `old('campo', 'padrao')` e `errors('campo')`: Recuperadores vitais de Sessão Flash para UI/UX de Formulários.
