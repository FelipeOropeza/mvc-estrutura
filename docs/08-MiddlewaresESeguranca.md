# Middlewares e Segurança

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
        // Tem sessão aberta? Se não, barra os invasores redirecionando para a página de login
        if (!session()->get('usuario_id')) {
            return Response::makeRedirect('/login');
        }

        // Tudo OK, pode seguir a vida!
        return $next($request);
    }
}
```

## Configuração de Middlewares (Apelidos e Grupos)
No arquivo `config/middleware.php`, nós gerimos como o sistema injeta ou agrupa filtros ao redor de rotas.
* **globais:** Rodam em todas as rotas (Como Iniciador de Sessões e tratamento CORS).
* **aliases:** Exemplo: Associar a string `'auth'` à classe `AuthMiddleware::class`. Isso permite chamar na rota: `->middleware('auth')`.
* **admin:** O scaffold `php forge setup:auth` já cria o `AdminMiddleware::class` para você gerenciar permissões de cargo/role de forma simples.
* **groups:** Agrupar middlewares para blocos de rotas parecidas (ex: `web` para Views HTML contendo proteção CSRF, ou `api` para Endpoints).

Exemplo de uso na Rota (com array ou apelidos):
```php
// Com String Alias (apelido) configurado em config/middleware.php
Route::get('/painel', [PainelController::class, 'index'])->middleware('auth');

// Com Arrays de classes:
Route::get('/seguro', [SafeController::class, 'index'])->middleware([\App\Middleware\AuthMiddleware::class]);
```

## Proteção Nativa (CSRF Forms)
Sempre que for submeter um Database (POST/DELETE/PUT), você **precisa** adicionar o campo oculto mágico gerado na View que contorna a submissão CSRF gerada por um atacante (VerifyCsrfToken é nativamente habilitado no núcleo do App).
```html
<form action="/salvar" method="POST">
    <?= csrf_field() ?> <!-- Vital -->
    <input type="text" name="cpf">
    <button>Confirmar Cadastro</button>
</form>
```
