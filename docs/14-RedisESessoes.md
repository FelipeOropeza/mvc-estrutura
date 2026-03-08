# Cache e Sessões em Alta Velocidade (Redis)

O framework suporta dois drivers de sessão: **File** (padrão) e **Redis** (alta performance para produção).

---

## Configuração do Driver

No seu `.env`:

```env
# Padrão — sem dependência extra
SESSION_DRIVER=file

# Alta performance para produção (requer extensão Redis no PHP)
SESSION_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=senha_se_houver
```

---

## Por que Redis?

| | File Session | Redis Session |
|---|---|---|
| **Velocidade** | I/O de disco | RAM (µs) |
| **Concorrência** | Bloqueio por arquivo | Sem bloqueio |
| **Worker Mode** | Pode travar | Ideal |
| **Multi-servidor** | Não compartilha | Compartilhado |

O `RedisSessionHandler` desativa o GC tradicional do PHP (`session.gc_probability=0`) porque o Redis gerencia o TTL automaticamente via `SETEX`.

**Fallback automático:** Se o Redis cair, o sistema cai automaticamente para FileSession, garantindo que o app continue funcionando.

---

## API da Sessão

```php
// Ler
$userId = session('usuario_id');
$userId = session()->get('usuario_id', null); // com default

// Escrever
session()->set('usuario_id', 42);

// Verificar
session()->has('usuario_id'); // bool

// Remover
session()->remove('carrinho');

// Destruir tudo (logout)
session()->destroy();

// Todos os dados
$tudo = session()->all();
```

---

## Flash Messages

Dados que vivem **apenas por uma requisição** (desaparecem após serem lidos):

```php
// Definir (antes de redirecionar)
session()->flash('success', 'Produto salvo com sucesso!');
session()->flash('error', 'Algo deu errado.');

// Ler na próxima View (já vem via session())
$msg = session('success'); // "Produto salvo com sucesso!"
```

---

## CSRF Token

O token é gerado automaticamente na primeira requisição e persistido na sessão:

```php
// Em formulários:
<?= csrf_field() ?>
// Gera: <input type="hidden" name="_token" value="abc123...">

// Verificação é automática pelo middleware VerifyCsrfToken
// Para excluir rotas da verificação, edite config/middleware.php
```

---

## Segurança: Regenerar Sessão após Login

Para prevenir **Session Fixation attacks**, sempre regenere a sessão após um login bem-sucedido:

```php
public function login(LoginDTO $dto): Response
{
    $usuario = (new Usuario())->where('email', $dto->email)->first();

    if (!$usuario || !password_verify($dto->senha, $usuario->password)) {
        fail_validation('email', 'Credenciais inválidas.');
    }

    session()->set('usuario_id', $usuario->id);
    session()->set('cargo', $usuario->cargo);

    // ✅ SEMPRE faça isso após login — gera novo session ID e novo CSRF token
    session()->regenerate();

    return redirect('/dashboard');
}
```
