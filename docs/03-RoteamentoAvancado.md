# Roteamento Avançado

As rotas ficam no arquivo `routes/web.php`. Nelas, declaramos a URL e qual Controller deve assumir esse acesso. Você pode usar tanto a variável `$router` quanto a Facade estática `Route`.

**Rotas Básicas:**
```php
use App\Controllers\HomeController;
use Core\Routing\Route;

// Usando a Facade Route (Recomendado/Laravel Style)
Route::get('/', [HomeController::class, 'index']);
Route::post('/contato/enviar', [HomeController::class, 'store']);

// Ou usando a instância do $router
$router->get('/sobre', [PageController::class, 'about']);
```

**Parâmetros Dinâmicos e Nomenclatura de Rota:**
Você pode capturar informações na URL e batizar sua rota para facilitar a criação de Links na View de forma dinâmica e inquebrável caso a URL mude no futuro.
```php
Route::get('/produto/{id}', [ProdutoController::class, 'show'])->name('produto.detalhe');

// Na sua view (se usar a Engine nativa PHP compatível com o Helper):
// <a href="<?= route('produto.detalhe', ['id' => 5]) ?>"> Detalhes </a>
```

### Roteamento via Atributos (PHP 8+)
O framework suporta a definição de rotas diretamente nos métodos dos Controllers através de Atributos. Isso ajuda a manter a lógica e a rota no mesmo lugar.

**Exemplo no Controller:**
```php
namespace App\Controllers;

use Core\Attributes\Route\Get;
use Core\Attributes\Route\Post;
use Core\Attributes\Route\Middleware;

class ProdutoController {
    #[Get('/produtos', name: 'produtos.index')]
    public function index() { ... }

    #[Get('/produto/{id}', name: 'produtos.show')]
    #[Middleware(AuthMiddleware::class)]
    public function show($id) { ... }
}
```

Agora você pode usar o helper `route('produtos.index')` mesmo para rotas definidas via atributos!

> [!TIP]
> Quando usar Atributos, você não precisa registrar a rota no `web.php`. O framework faz um scanner automático da pasta `app/Controllers`.

// No seu ProdutoController:

```php
public function show($id) {
    echo "Pesquisando pelo produto de número: " . $id;
}
```

**Grupos de Rotas e Middlewares Acoplados:**
Ideal para painéis administrativos (Ex: exigir que toda a rota `/admin/...` passe pela validação de Login).
```php
Route::group(['prefix' => '/admin', 'middleware' => [AuthMiddleware::class]], function() {
    Route::get('/dashboard', [AdminController::class, 'index']);
    Route::get('/usuarios', [AdminController::class, 'users']);
});
```
