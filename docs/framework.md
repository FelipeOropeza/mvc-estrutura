# Documentação Oficial: MVC Base Framework

Bem-vindo ao manual completo do **MVC Base**, um micro-framework ultra-rápido, arquitetado em conceitos modernos (Stateless, PSR-11 e PSR-15 concept) preparado para o FrankenPHP. Aqui você aprenderá a dominar cada engrenagem para construir desde blogs até e-commerces e sistemas complexos.

---

## Índice

1. [Estrutura de Diretórios](#1-estrutura-de-diretórios)
2. [Roteamento Avançado](#2-roteamento-avançado)
3. [Controllers e HTTP](#3-controllers-e-http)
4. [Banco de Dados (ORM Moderno)](#4-banco-de-dados-orm-moderno)
5. [Validações e Atributos Mágicos](#5-validações-e-atributos-mágicos)
6. [Mutations (Mutadores de Dados)](#6-mutations-mutadores-de-dados)
7. [Middlewares e Segurança](#7-middlewares-e-segurança)
8. [Upload de Arquivos](#8-upload-de-arquivos)
9. [Views e Interface de Usuário (UI)](#9-views-e-interface-de-usuário-ui)
10. [Injeção de Dependências e Service Providers](#10-injeção-de-dependências-e-service-providers)
11. [CLI (Forge Console)](#11-cli-forge-console)
12. [Helpers Globais Globais](#12-helpers-globais)

---

## 1. Estrutura de Diretórios

O framework segue uma separação lógica e profissional de pastas:

- **`app/`**: Onde você vai passar 90% do seu tempo.
  - **`Controllers/`**: Orquestram as requisições e a lógica de negócios.
  - **`Models/`**: Representam as tabelas do Banco, comportam validações e relacionamentos.
  - **`Middleware/`**: "Filtros" (Ex: Bloquear usuários deslogados).
  - **`Views/`**: O visual do seu site (HTML/PHP ou Twig).
  - **`Mutators/`** e **`Rules/`**: Suas Inteligências Mágicas criadas para manipular e validar campos.
  - **`Providers/`**: Seus registradores de serviços de inicialização.
- **`config/`**: Configurações (`app.php` para sistema e `database.php` para o banco de dados).
- **`core/`**: O motor do framework (Não mexa aqui dentro a não ser que vá contribuir com a arquitetura núcleo da engine).
- **`database/`**: Configurações de Banco e **`migrations/`** de tabelas.
- **`public/`**: A única pasta com acesso via Web (Contém o seu Arquivo `index.php` e os seus CSS/JS/Imagens).
- **`routes/`**: Define as URLs e Grupos de URLs disponíveis no seu App (`web.php`).
- **`storage/logs/`**: Logs de erros escondidos (`app.log`) ocorridos em Produção.

---

## 2. Roteamento Avançado

As rotas ficam no arquivo `routes/web.php`. Nelas, declaramos a URL e qual Controller deve assumir esse acesso.

**Rotas Básicas:**
```php
use App\Controllers\PageController;

$router->get('/home', [PageController::class, 'index']);
$router->post('/contato/enviar', [PageController::class, 'store']);
```

**Parâmetros Dinâmicos:**
Você pode capturar informações diretamente na URL.
```php
$router->get('/produto/{id}', [ProdutoController::class, 'show']);

// No seu ProdutoController:
public function show($id) {
    echo "Pesquisando pelo produto de número: " . $id;
}
```

**Grupos de Rotas e Middlewares Acoplados:**
Ideal para painéis administrativos (Ex: exigir que toda a rota `/admin/...` passe pela validação de Login).
```php
$router->group('/admin', [AuthMiddleware::class], function($router) {
    $router->get('/dashboard', [AdminController::class, 'painel']);
    $router->get('/usuarios', [AdminController::class, 'listaDeUsuarios']);
});
```

---

## 3. Controllers e HTTP

O Controller nunca deve dar um "echo". Ele sempre __retorna__ uma Resposta.

**Devolvendo JSON (Para APIs) ou Redirecionando:**
```php
namespace App\Controllers;

use Core\Http\Request;
use Core\Http\Response;

class ApiController 
{
    public function obterItens()
    {
        return Response::makeJson(['status' => 'sucesso', 'data' => [1, 2, 3]]);
    }
    
    public function salvar(Request $request)
    {
        // Se der sucesso... redirecione de volta para o menu:
        return Response::makeRedirect('/menu-principal');
    }
}
```

**Voltando atrás em formulários (Back):**
Muitas vezes você quer devolver o usuário para a última tela que ele estava.
```php
return Response::makeRedirectBack();
```

---

## 4. Banco de Dados (ORM Moderno)

Suas Models em `app/Models` representam suas tabelas do DB e são turbinadas com um **Query Builder**.

### 4.1 Buscas e Query Builder Fluente
Ao invés de programar SQL na mão, encadeie as instruções fluentemente.

```php
use App\Models\Produto;

$produtoModel = new Produto();

// Buscar todos ativados maiores que R$50.00
$produtosCaros = $produtoModel->select('nome, preco')
    ->where('ativo', '1')
    ->where('preco', '>', 50)
    ->orderBy('preco', 'DESC')
    ->limit(10)
    ->get(); // $produtosCaros é um Array de instâncias [Produto]

// Pegar APENAS UM registro
$meuArroz = $produtoModel->where('nome', '=', 'Arroz')->first();
echo $meuArroz->preco;
```

### 4.2 Lógica de Join
Use Inner ou Left Joins sem precisar escrever uma linha de SQL crua:
```php
$produtosComCategoria = $produtoModel->select('produtos.*, categorias.titulo')
    ->join('categorias', 'categorias.id = produtos.categoria_id', 'INNER')
    ->get();
```

### 4.3 Relacionamentos de Model Mágicos
Se o sistema for complexo, crie o relacionamento direto na Model. 

**Model Produto (Ele Pertence a uma Categoria):**
```php
class Produto extends Model {
    public function categoria(): ?Categoria {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }
}
```
**Model Categoria (Ela possui Vários Produtos):**
```php
class Categoria extends Model {
    public function produtos(): array {
        return $this->hasMany(Produto::class, 'categoria_id');
    }
}
```
**Como usar as Relações Pelo Controller:**
```php
$cat = (new Categoria())->find(1);
$todosOsProdutosDessaCategoria = $cat->produtos(); // Mágico!

$prod = (new Produto())->find(10);
echo $prod->categoria()->titulo; // Traz o registro dono do produto
```

### 4.4 Mass Assignment e Proteção
No seu Model, a propriedade `$fillable` protege contra ataques hackers tentando injetar colunas como `nivel_admin = 1` pelo inspecionar do navegador. O Insert blindará os dados indesejados automaticamente:
```php
class User extends Model {
    protected array $fillable = ['email', 'password'];
}
```

---

## 5. Validações e Atributos Mágicos

Use as novas regras de **Attributes (PHP 8)** diretamente dentro da classe Model. Chega de `if` no Controller!

### 5.1 Regras Mágicas Disponíveis e Suas Propriedades
Você pode engatilhar uma mensagem de erro totalmente em português nas configurações:

```php
use Core\Attributes\Required;
use Core\Attributes\Min;
use Core\Attributes\Email;
use Core\Attributes\Image;
use Core\Attributes\MatchField;

class Usuario extends Model
{
    // Required garante que não seja vazio e aceita customizar o texto 
    #[Required('Ei, você esqueceu de preencher o CPF.')]
    public ?string $cpf = null;

    // Regras acopladas uma abaixo da outra:
    #[Required('Digite um E-mail')]
    #[Email('Esse não parece ser um E-mail válido.')]
    public ?string $email = null;

    // Número Mínimo/Máximo de Caracteres, Valor Numérico ou Elementos de um Array
    #[Required]
    #[Min(8, 'Precisamos que a senha tenha no mínimo 8 dígitos, pra sua segurança')]
    public ?string $password = null;

    // Valida se a Confirmação de Senha é igual à Senha
    #[MatchField('password', 'As senhas não conferem')]
    public ?string $password_confirm = null;
    
    // Valida Booleanos estritos (true, false, '1', '0')
    #[IsBool('O Aceite de termos deve ser Sim ou Não')]
    public ?bool $aceita_termos = null;
    
    // Validação estrita de Floats Simulando Database. Ex (5 Precisão Total Numérica , 2 Decimais): Limit: 999.99
    #[IsFloat(5, 2, 'Dinheiro incompatível.')]
    public ?float $saldo = null;
}
```

### 5.2 Rodando a Validação 
No `Controller`, a validação devolve somente dados seguros, ou trava a navegação e avisa a página anterior ativando o helper de erro de interface:
```php
public function criar(Request $request)
{
    $dados = $request->all();
    
    $userModel = new Usuario();
    $userModel->fill($dados);
    
    $seguros = $userModel->validate(); // Se falhar ele envia o erro e cancela a rota automaticamente
    
    $userModel->insert($seguros);
    return Response::makeRedirect('/sucesso');
}
```

### 5.3 Validação Manual no Controller
Caso a validação não sirva pra banco de dados (exemplo, processar Cartão na Pagar.me e devolver erro no visual pro usuário):
```php
$pagou = $pagarMe->transacionar($cartao);
if (!$pagou) {
    fail_validation('cartao', 'Limite Recusado pelo seu Banco.');
    // Isso cancela a roda, reflete na variavel de sessao e devolve na interface a mensagem "Limite..".
}
```

### 5.4 Criando suas Próprias Regras!
Use a Forja do Console (CLI):
```bash
php forge make:rule DocumentoCpf
```
Edite a Lógica (`app/Rules/DocumentoCpf.php`) para testar DB, Matemática, Regex. Depois apenas instale-a no seu Model `#[DocumentoCpf]`.

---

## 6. Mutations (Mutadores de Dados)

Permitem converter/sanitizar/criptografar silenciosamente um dado capturado de formulário ANTES de enviá-lo de fato ao Banco, usando também atributos mágicos do PHP8.

### O Mutator Nativo: Criptografia de Senhas
Na sua Model User:
```php
use App\Models\Model;
use Core\Attributes\Hash; // Usa password_hash no preenchimento

class User extends Model {
    #[Hash]    
    public ?string $password = null; 
    // Quando você salvar Rato123 na model e chamar O insert($data), ele vai salvar $2b$10$xyz no banco mágico sozinho.
}
```

### Criando Seu Próprio Mutator (Exemplo de limpeza de pontos do CPF)
Use o comando de motor CLI (Forge):
```bash
php forge make:mutator LimpaCpf
```
Entre na lógica `app/Mutators/LimpaCpf.php` configurando a regex e instale-a assim na Model:
```php
use App\Mutators\LimpaCpf;

class Fornecedor extends Model {
    #[Required]
    #[LimpaCpf] 
    // Ele vai pegar "124.550.212-00" limpo pra "12455021200" e encaminhar como string final pro insert().
    public ?string $cpf = null; 
}
```

---

## 7. Middlewares e Segurança

Eles são a espinha dorsal de Defesa do Framework. Ao contrário de frameworks antigos que usavam `if` ou `exit` dentro do cabeçalho de telas, os Middlewares sempre devolvem uma Resposta e o processamento é paralisado.

**Verificando Login e barrando acessos intrusivos:**
```php
namespace App\Middleware;

use Core\Http\Request;
use Closure;
use Core\Http\Response;

class AuthMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // Tem sessão aberta? Se não, barra os invasores redirecionando para a pagina de login
        if (!session()->get('usuario_id')) {
            return Response::makeRedirect('/login');
        }

        // Tudo OK, pode seguir a vida!
        return $next($request);
    }
}
```

### Proteção Nativa (CSRF Forms)
Sempre que for submeter um Database (POST/DELETE/PUT), você **precisa** adicionar o campo oculto mágico gerado na View que contorna a submissão CSRF gerada por um atacante (VerifyCsrfToken é nativamente habilitado no núcleo do App).
```html
<form action="/salvar" method="POST">
    <?= csrf_field() ?> <!-- Vital -->
    <input type="text" name="cpf">
    <button>Confirmar Cadastro</button>
</form>
```

---

## 8. Upload de Arquivos

Arquivos `$_FILES` foram totalmente remodelados e são recebidos como Instâncias super seguras Orientadas a Objetos: `UploadedFile`.

Para validar nas Models:
```php
// Aceita qualquer Binário de no máx 10 MB.
#[File(maxSize: 10485760)] 

// Exige Especificamente que a Imagem passe num funil severo para barrar uploads perigosos mascarados. Max 2MB, jpg e png.
#[Image(maxSizeMb: 2, mimes: ['image/jpeg', 'image/png'], message: "A CNH anexada não bate com nada do que fomos configurados pra aceitar!")]
public ?\Core\Http\UploadedFile $arquivocnh = null;
```

Mova este arquivo validado do Local Temporário direto para onde quiser dentro do Controller de Resposta:
```php
public function store(Request $request) {
    if ($request->hasFile('foto')) {
        $arquivo = $request->getFile('foto');
        $destinoFinal = __DIR__ . '/../../public/uploads/' . $arquivo->getClientFilename();
        
        $arquivo->moveTo($destinoFinal);
    }
}
```

---

## 9. Views e Interface de Usuário (UI)

Temos a view engatilhada a retornar PHP ou TWIG baseado no motor setado em `config/app.php`. 

Para renderizar:
```php
return view('produto/detalhes', [
    'nome' => 'Sabão em pó',
    'preco' => 12.00
]);
```

### Retornando feedbacks e erros do Validate() na Interface
O Framework mantém sessões invisíveis "Flash" que expiram e apagam no Reload seguinte da tela para lidar os formulários rejeitados.

```php
<form method="POST" action="/submeter">
    <?= csrf_field() ?>
    
    <!-- Mantenha o que o cara digitou usando old() se ele errou algo e foi redirecionado pra ca dnv -->
    <input type="text" name="email" value="<?= old('email') ?>">
    
    <!-- Mostre O ERRO mágico do #[Email('..')] de sua Model abaixo do Campo usando o errors()! -->
    <span style="color:red;"><?= errors('email') ?></span>
</form>
```

---

## 10. Injeção de Dependências e Service Providers

O motor MVC local é super alimentado, possuindo um Inversor de Controle e Container Integrados. Isso significa que, em seus controllers e configurações, você nunca precisará mais construir `$conexao = new PDO...` na mão usando Singletons sujos pelo disco.

Para utilizar uma Conexão do seu Banco de Dados já instanciada magicamente pelo núcleo:
```php
$minhaVariavelGlobalSeguraEMagica = app(PDO::class);
```

### Service Providers
Localizados em `app/Providers/`. São as Centrais de Distribuição de Conhecimento para o Site iniciar de forma robusta e inteligente (Como o Lifecycle do Laravel) . Liste-os no `config/app.php` para o motor incluí-los na Partida Principal e Registre ali bibliotecas gigantes (`Stripe`, `Pagar.me`, `RedisServer`).

---

## 11. CLI (Forge Console)

Escrever código na mão é amadorismo. O `php forge` é um gerador visual super útil acessível pelo prompt de comandos!

**Lista Rápida Baseada no Motor Padrão:**
```bash
# Entenda as capacidades disponíveis no App Local:
php forge

# Geradores Rápidos - "Make":
php forge make:controller UsuarioController  # Na Pasta /Controllers
php forge make:model Fornecedor             # Na Pasta /Models com $fillable pre-pronto
php forge make:middleware TravaIP           # Na Pasta /Middleware 
php forge make:view relatorios/financeiro   # Gera HTMLs limpos e alinhados num padrão

# Criação de Lógicas Magicas Injetáveis Direto Dentro Da Model
php forge make:rule NomeDaSuaValidadora      # Pasta /Rules
php forge make:mutator NomeDaSuaMutaçãoLimpeza # Pasta /Mutators

# Compiladores Finais
php forge setup:engine twig        # Migra o projeto entre Php/Twig como View padrão do Front   
php forge migrate                  # Roda e executa as classes presentes da pasta Core.
php forge optimize                 # Escala pra Nuvem compilando configs em memória máxima    
```

---

## 12. Helpers Globais

Atalhos diretos da Programação para facilitar implementações cruciais.
* `app()`: Devolve a base de Container.
* `logger()->info("Salvo com sucesso")`: Uma forma maravilhosa de ler ocorrências sem atrapalhar e avisar o usuário que teve Exceção. Vai silenciado ao arquivo `/storage/logs/`.
* `request()`: Abstrai toda a URL da Web que o usuário navegante acessou e todos seus Headers Seguros.
* `session()`: Lê variaveis que transitam pela RAM do Framework até sua View. Use `session()->flash('success', 'Cadastrado')`.
* `view()`: Chamada principal de Views (Ex: `view('painel/index', ...)` ).
* `old('nome_do_campo')`: Recupera lógicas mal preenchidas.
* `errors('nome_do_campo')`: Apresenta erros do Validator em tempo real na Interface da WEB.
