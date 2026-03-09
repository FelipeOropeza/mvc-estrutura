# ⚡ 20. Sistema de Cache

O sistema de cache permite armazenar dados temporários para reduzir o acesso ao banco de dados e acelerar a aplicação.

## ⚙️ Configuração

Configure o driver desejado no `.env`:

```env
CACHE_DRIVER=file  # Opções: file, redis
```

## 📖 Como Usar

A forma mais eficiente de usar o cache é através do método `remember`:

```php
use Core\Cache\CacheManager;

$usuarios = CacheManager::remember('todos_usuarios', 3600, function() {
    return User::all(); // Só executa se não estiver no cache
});
```

### Métodos Manuais

```php
// Salvar no cache por 1 hora (3600 segundos)
CacheManager::set('chave', $valor, 3600);

// Recuperar
$valor = CacheManager::get('chave');

// Verificar existência
if (CacheManager::driver()->has('chave')) {
    // ...
}

// Remover
CacheManager::driver()->forget('chave');
```

## 🗄️ Drivers

- **File**: Armazena em arquivos dentro de `storage/cache`.
- **Redis**: Armazena em memória no servidor Redis para velocidade máxima.
