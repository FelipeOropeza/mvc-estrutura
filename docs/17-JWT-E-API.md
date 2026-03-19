# 📡 17. JWT & API Stateless

O framework possui suporte nativo para criação de APIs Stateless utilizando **JWT (JSON Web Tokens)**. Diferente da aplicação Web tradicional, as APIs não utilizam sessões em cookies, garantindo escalabilidade.

## 🚀 Scaffold Automático

Você pode gerar toda a estrutura necessária para uma API com um único comando:

```bash
php forge setup:api
```

**Este comando cria:**
- `app/Controllers/Api/AuthController.php`: Login e endpoint `/me`.
- `app/DTOs/Api/LoginDTO.php`: Validação de entrada.
- `app/Services/Api/AuthService.php`: Lógica de autenticação.
- `app/Models/User.php`: Model com suporte a tokens.
- `app/Middleware/AuthApiMiddleware.php`: Validador de JWT.
- `routes/api.php`: Arquivo de rotas exclusivo para API.

## 🔑 TokenManager

A classe `Core\Auth\TokenManager` é responsável por gerar e validar os tokens.

```php
use Core\Auth\TokenManager;

$manager = new TokenManager();

// Gerar Token
$token = $manager->generateToken($userId);

// Validar Token
$payload = $manager->validateToken($token);
```

## 🛡️ Protegendo Rotas

Utilize o middleware `auth.api` para proteger seus endpoints. O framework detecta automaticamente se a requisição é de API (via prefixo `/api` ou header `Accept: application/json`) e desativa as sessões tradicionais para performance.

```php
use Core\Routing\Route;

Route::group(['middleware' => 'auth.api'], function() {
    Route::get('/api/perfil', [UserController::class, 'profile']);
});
```

## 📝 Exemplo de Resposta

O `Request` injeta o ID do usuário autenticado nos atributos:

```php
public function profile()
{
    $userId = request()->attributes['auth_user_id'];
    $user = (new User())->find($userId);
    
    return response()->json($user);
}
```

## 🏗️ Gerando Resources (API Controllers)

Para focar no desenvolvimento veloz, o framework possui a flag `--api` para a criação de Controllers. Em vez de retornar Views HTML, ele gera um Controller focado nas 5 ações padrão RESTful utilizando métodos que devolvem JSON bruto:

```bash
php forge make:controller Api/ProdutosController --api
```

Isto gera os métodos `index`, `show`, `store`, `update` e `destroy` baseados nos **Atributos HTTP do PHP 8** (`#[Get]`, `#[Post]`, etc...), mapeando rotas diretamente pro prefixo `/api/` sem a necessidade de cadastrá-las no `routes/web.php` ou `routes/api.php`!

## 🛡️ Auto-Validação JSON Exclusiva (DTOs)

Construir APIs demanda segurança da entrada de dados (Payloads). Felizmente o mecanismo de validação do MVC Base é 100% capaz de se comportar de forma inteligente para APIs.

### O comportamento de Resposta de Erro

Nas aplicações tradicionais HTML de *Full-Stack*, quando a validação de um formulário recusa os dados, o framework cancela a rota via _exception_ e dispara um comportamento de Sessão (*Flash Session*) que realiza um redirect *HTTP 302* de volta pro formulário preenchendo as tags de aviso no HTML.

**Contudo, quando a sua chamada é de uma API:**

O Framework Core mapeia automaticamente se a chamada HTTP da requisição possui as seguintes configurações:
1. `Accept: application/json` no Header da Request **OU**
2. Se a rota iniciou pelo prefixo URI `/api/`

Sendo verdadeiro qualuqer um destes testes, ao invés de buscar a Sessão, o Handler Global de Exceções captura o problema e ejeta uma resposta imediata **HTTP 422 Unprocessable Entity** via JSON para o front-end, parando a execução antes mesmo do Controller rodar:

**Como chega no Front-end (Nuxt, React, Flutter):**
```json
{
    "status": "error",
    "message": "Erro de Validação Atributiva",
    "errors": {
        "email": ["Esse não parece ser um E-mail válido.", "Este e-mail já está em uso."],
        "senha": ["Precisamos que a senha tenha no mínimo 8 dígitos, pra sua segurança"]
    }
}
```

### Exemplo de uso
Dessa forma, o seu fluxo de código no Controller para API, ao injetar o DTO via *Autowiring*, permanecerá absurdamente limpo! O programador de back-end foca apenas na lógica positiva.

```php
use App\DTOs\Api\CadastroDTO;
use Core\Http\Response;

class UsuariosController extends Controller
{
    #[Post('/api/usuarios')]
    public function store(Request $request, CadastroDTO $dto) // Injeta a DTO para validar magicamente
    {
        // Se a lógica chegou aqui SUCESSO! A requisição superou o JSON Validation Gatekeeper 422!
        $dadosSeguros = $dto->toArray();
        
        // Insira no Banco...
        // ...
        
        // E então você devolve o recurso ou mensagem:
        return Response::makeJson([
            'status' => 'sucesso',
            'data' => $dadosSeguros
        ], 201);
    }
}
```
