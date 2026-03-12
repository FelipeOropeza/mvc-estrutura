# Helpers Globais

Funções globais disponíveis em qualquer lugar da aplicação — Controllers, Views, Services, Middlewares.

---

## `app(?string $abstract)`
Acessa o Container de Injeção de Dependências. Sem argumento, retorna o Container. Com argumento, resolve a classe.
```php
$router = app(\Core\Routing\Router::class);
$container = app(); // O Container em si
```

---

## `request()`
Retorna a instância da `Request` atual com todos os dados da requisição.
```php
$email = request()->get('email');
$arquivo = request()->files['foto'];
$isHtmx = request()->isHtmx();
```

---

## `response(string $content, int $status, array $headers)`
Cria uma nova `Response`. Sempre retorna uma instância nova (sem estado compartilhado entre requisições).
```php
return response('<h1>Olá</h1>', 200);
return response(json_encode($data), 200, ['Content-Type' => 'application/json']);
```

---

## `redirect(string $url, int $status = 302)`
Retorna uma Response de redirecionamento. Use `return` em Controllers.
```php
return redirect('/login');
return redirect(route('admin.dashboard'), 301);
```

---

## `abort(int $code, string $message = '')`
Interrompe a requisição imediatamente com um código HTTP. O Handler renderiza a página de erro correta.
```php
abort(404);                          // Não encontrado
abort(403, 'Acesso negado.');        // Proibido
abort(401, 'Faça login primeiro.');  // Não autenticado
```

---

## `view(string $nome, array $data = [])`
Renderiza uma View e retorna a string HTML.
```php
return view('produtos/index', ['produtos' => $lista]);
```

---

## `e(mixed $valor)`
**Escapa HTML para exibição segura nas Views.** Use sempre que imprimir dados do usuário para prevenir XSS.
```php
// Na view:
echo e($produto->nome);          // "Arroz <br> Feijão" → "Arroz &lt;br&gt; Feijão"
echo '<h1>' . e($titulo) . '</h1>';
```
> ⚠️ **Não** use `htmlspecialchars()` diretamente. O `e()` usa as flags corretas (`ENT_QUOTES | ENT_SUBSTITUTE`).

---

## `validate(object $dto)`
Valida os dados da request contra um DTO. Lança uma `ValidationException` automaticamente se falhar.
```php
$dados = validate(new ProdutoDTO());
```

---

## `fail_validation(string|array $field, ?string $message)`
Lança manualmente um erro de validação para interromper a rota e redirecionar com mensagem.
```php
if (!$pagamentoAprovado) {
    fail_validation('cartao', 'Cartão recusado pela operadora.');
}

// Múltiplos erros:
fail_validation(['email' => ['Já cadastrado.'], 'cpf' => ['CPF inválido.']]);
```

---

## `errors(?string $field)`
Recupera erros de validação flash da sessão para exibir nas Views.
```php
echo errors('email');    // "E-mail inválido."
$todos = errors();       // Array com todos os erros
```

---

## `old(string $field, mixed $default = '')`
Recupera o valor que o usuário digitou antes de um erro de validação (para repopular formulários).
```php
<input name="email" value="<?= e(old('email')) ?>">
```

---

## `session(?string $key, mixed $default)`
Acessa a sessão atual. Sem argumentos, retorna a instância de `Session`.
```php
session()->set('usuario_id', 42);
$id = session('usuario_id');
session()->flash('success', 'Salvo com sucesso!');
session()->regenerate(); // Chame após login para prevenir Session Fixation!
```

---

## `csrf_token()`
Retorna o token CSRF da sessão atual.
```php
$token = csrf_token();
```

## `csrf_field()`
Retorna o campo hidden HTML com o token CSRF. Inclua em todos os formulários `POST/PUT/DELETE`.
```php
<form method="POST">
    <?= csrf_field() ?>
    ...
</form>
```

---

## `route(string $name, array $params = [])`
Gera a URL para uma rota nomeada.
```php
$url = route('produto.show', ['id' => 5]);
// Resultado: "/produtos/5"
```

---

## `storage_url(?string $path)`
Gera a URL pública para arquivos na pasta `/storage`. Aceita URLs absolutas sem modificação.
```php
<img src="<?= storage_url($produto->imagem_url) ?>">
// Resultado: "/storage/produtos/foto.jpg"
```

---

## `env(string $key, mixed $default = null)`
Lê variáveis de ambiente do `.env`. Converte automaticamente `true/false/null` para tipos PHP.
```php
$debug = env('APP_DEBUG', false); // bool
$dsn   = env('DB_HOST', '127.0.0.1');
```

---

---

## `storage_path(string $path = '')`
Retorna o caminho absoluto para o diretório de armazenamento (`/storage`).
```php
$path = storage_path('app/uploads/foto.jpg');
// Resultado: "C:/.../storage/app/uploads/foto.jpg"
```

---

## `broadcast(string $topic, array $data)`
Dispara um evento em tempo real para o **Mercure Hub**. Qualquer cliente ouvindo este tópico receberá os dados instantaneamente.
```php
broadcast('chat/sala-1', ['message' => 'Olá pessoal!', 'user' => 'Felipe']);
```

---

## `mercure_listen(string $topic, string $htmxTriggerName)`
**Helper de View.** Gera um componente `<script>` que escuta um tópico do Mercure e dispara um evento do HTMX no navegador.
```php
// Na view:
<?= mercure_listen('chat/sala-1', 'refresh-chat') ?>

<div hx-get="/chat/messages" hx-trigger="refresh-chat from:body">
    <!-- As mensagens serão recarregadas via HTMX quando houver um broadcast -->
</div>
```

---

## `logger()`
Retorna a instância do Logger para gravar no arquivo `storage/logs/app.log`.
```php
logger()->info('Produto criado', ['id' => $id]);
logger()->error('Falha no pagamento', ['erro' => $e->getMessage()]);
```
