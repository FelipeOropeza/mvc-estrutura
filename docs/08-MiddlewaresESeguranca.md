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

### Recebendo Parâmetros em Middlewares

Você pode passar parâmetros extras para o seu middleware na definição da rota usando a sintaxe `:param1,param2`. Eles serão injetados logo após o closure `$next`:

```php
// Na definição da Rota:
Route::get('/admin', [AdminController::class, 'index'])->middleware('auth:admin,editor');

// No Middleware:
public function handle(Request $request, Closure $next, string ...$roles)
{
    // $roles será ['admin', 'editor']
    if (!in_array(session()->get('cargo'), $roles)) {
        return Response::json(['error' => 'Acesso negado'], 403);
    }

    return $next($request);
}
```

## Configuração de Middlewares (Apelidos e Grupos)
No arquivo `config/middleware.php`, nós gerimos como o sistema injeta ou agrupa filtros ao redor de rotas.
* **globais:** Rodam em todas as rotas. Atualmente incluem:
    * `SecurityHeaders`: Proteção contra Clickjacking e XSS.
    * `HandleCors`: Gerenciamento de acessos externos.
    * `StartSession`: Inicialização automática da sessão.
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

---

## Proteção de Cabeçalhos (Security Headers)

O Framework injeta automaticamente cabeçalhos de segurança em todas as respostas através do middleware global `SecurityHeaders`:

*   **X-Frame-Options: SAMEORIGIN**: Impede que seu site seja carregado dentro de frames/iframes de outros domínios (Clickjacking).
*   **X-Content-Type-Options: nosniff**: Impede o navegador de tentar adivinhar o tipo de conteúdo, forçando o uso do MIME type declarado.
*   **X-XSS-Protection**: Ativa proteções básicas de XSS no navegador.
*   **Referrer-Policy**: Controla quais informações de referência são enviadas ao navegar para outros sites.

---

## Proteção Nativa (CSRF Forms)

A proteção CSRF agora está **ativada por padrão** no grupo de rotas `web` através do middleware `VerifyCsrfToken`. 

Sempre que for submeter um formulário (POST/DELETE/PUT), você **precisa** adicionar o campo oculto gerado pelo helper `csrf_field()`:
```html
<form action="/salvar" method="POST">
    <?= csrf_field() ?> <!-- Vital -->
    <input type="text" name="cpf">
    <button>Confirmar Cadastro</button>
</form>
```
