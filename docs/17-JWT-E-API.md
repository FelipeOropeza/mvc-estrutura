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
