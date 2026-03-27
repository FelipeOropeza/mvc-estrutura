# Guia: Model Auto-Broadcasting em Tempo Real

O framework agora suporta a sincronização automática de dados entre o Banco de Dados e o Navegador em tempo real.

## 1. Como habilitar na Model

Basta adicionar o atributo `#[Broadcast]` à sua Model. Por padrão, ele usará o nome da tabela como tópico e disparará um evento chamado `refresh`.

```php
namespace App\Models;

use Core\Database\Model;
use Core\Attributes\Broadcast;

#[\Core\Attributes\Broadcast(topic: 'produtos', event: 'att-lista')]
class Produto extends Model
{
    /** @var array */
    protected array $fillable = ['nome', 'preco'];
}
```

## 2. Como escutar na View (HTMX)

Na sua View, use o helper `mercure_listen()` para escutar as mudanças e o HTMX para reagir a elas.

```html
<!-- Ouve o tópico 'produtos' e dispara o evento 'att-lista' no DOM -->
<?= mercure_listen('produtos', 'att-lista') ?>

<div id="lista-produtos" 
     hx-get="/produtos/parcial" 
     hx-trigger="att-lista from:body">
    <!-- Este conteúdo será recarregado via HTMX sempre que um Produto for salvo! -->
    <?php foreach($produtos as $p): ?>
        <p><?= e($p->nome) ?> - R$ <?= e($p->preco) ?></p>
    <?php endforeach; ?>
</div>
```

## O que acontece nos bastidores?
1. Quando você faz `$produto->save()`, o framework detecta o atributo `#[Broadcast]`.
2. Ele envia instantaneamente um sinal para o **Mercure Hub**.
3. O navegador recebe o sinal via SSE (Server-Sent Events).
4. O HTMX captura o evento e atualiza o pedaço específico da página.

**Performance:** O framework faz o cache da detecção do atributo, garantindo que o impacto no tempo de execução seja quase zero.
