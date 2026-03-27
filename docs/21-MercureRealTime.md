# Eventos em Tempo Real (Mercure Hub)

O framework MVC Base possui suporte nativo ao **Mercure**, um protocolo aberto para "push" de dados para navegadores de forma ultra-eficiente via SSE (Server-Sent Events). 

Ao usar o **FrankenPHP**, o Hub Mercure já vem configurado e pronto para uso, permitindo que você crie chats, notificações e dashboards que se atualizam sozinhos sem que o usuário precise dar F5.

---

## ⚡ Scaffold Rápido de Avisos

Se você quer ver a mágica acontecer agora mesmo, o framework conta com um gerador completo de sistema de avisos:

```bash
php forge setup:aviso
```

**Este comando gera automaticamente:**
- **Migration**: Tabela `avisos` pronta no banco.
- **Model**: Classe `Notice` para gerenciar os dados.
- **Controller**: `NoticeController` com lógica de envio e listagem.
- **Views**: Telas de gerenciamento (`/avisos`).
- **Componente reativo**: Componente HTMX que se auto-atualiza via Mercure.

---

## Como Funciona?

1. **Backend (Broadcast):** Você dispara um evento usando o helper `broadcast()`.
2. **Frontend (Listener):** O navegador escuta esse tópico e, quando recebe os dados, executa uma ação (geralmente via HTMX para atualizar um pedaço da tela).

---

## 1. Disparando Eventos (Broadcasting)

Sempre que algo importante acontecer no seu Controller ou Service, você pode avisar o mundo:

```php
public function store() {
    // ... lógica de salvar no banco
    
    // Notifica todos que estão ouvindo o tópico 'notificacoes'
    broadcast('notificacoes', [
        'message' => 'Um novo produto foi cadastrado!',
        'user' => session('user_name')
    ]);
}
```

---

## 2. Automação via Models (Atributo Broadcast)

Em vez de disparar manualmente no Controller, você pode automatizar o broadcast diretamente na sua **Model** usando PHP 8 Attributes. Isso garante que sempre que um registro for criado ou editado, o evento seja disparado.

```php
namespace App\Models;

use Core\Database\Model;
use Core\Attributes\Broadcast;

#[Broadcast(topic: 'produtos', event: 'att-lista', mode: 'all', with: 'categoria')]
class Produto extends Model
{
    public function categoria() {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }
}
```

### Parâmetros do Atributo:

| Parâmetro | Tipo | Descrição |
|---|---|---|
| `topic` | `string` | O nome do canal Mercure. Se omitido, usa o nome da tabela. |
| `event` | `string` | O nome do evento customizado disparado no navegador (padrão: `refresh`). |
| `mode` | `string` | Controla quando disparar: `all` (padrão), `create` (apenas insert) ou `update` (apenas update). |
| `with` | `string\|array` | Carrega os relacionamentos do Model antes de disparar o broadcast, garantindo que o JSON enviado ao frontend contenha os dados aninhados. |

---

## 3. Boas Práticas

### Performance e UX
- **Eventos Granulares**: Não dispare eventos `all` para tudo se o frontend só precisa saber de criações. Use o `mode: 'create'` para otimizar.
- **Payload Mínimo**: O broadcast automático envia o `toArray()` da Model. Se a model for muito grande, considere usar o helper `broadcast()` manual apenas com o ID do registro para o frontend buscar o resto.
- **Segurança**: Nunca envie dados sensíveis (senhas, chaves de API) no payload do broadcast. Lembre-se que SSE (Server-Sent Events) pode ser interceptado se não estiver sob HTTPS.

### HTMX Integration
Sempre use o modificador `from:body` no `hx-trigger` do HTMX, pois o framework dispara os eventos de forma global para permitir que múltiplos componentes reajam ao mesmo sinal.

---

## 4. Escutando no Frontend (HTMX + Mercure)

O framework facilita a integração com o HTMX através do helper `mercure_listen`.

No seu arquivo de View:

```php
<!-- 1. Inicia o ouvinte para o tópico -->
<?= mercure_listen('notificacoes', 'novo-evento') ?>

<!-- 2. Diz ao HTMX para reagir ao evento 'novo-evento' -->
<div hx-get="/notificacoes/lista" 
     hx-trigger="novo-evento from:body">
    <!-- Este conteúdo será recarregado automaticamente via AJAX toda vez que houver um broadcast -->
    <?php include 'partials/lista.php'; ?>
</div>
```

### O que acontece por baixo dos panos?
- O `mercure_listen` cria um `EventSource` que se conecta ao Hub.
- Quando o Hub recebe um sinal, o script dispara um `CustomEvent` no `document.body`.
- O HTMX, configurado com `hx-trigger="nome-do-evento from:body"`, detecta o evento e faz a requisição `GET` para atualizar o componente.

---

## 5. Configuração (.env)

No seu arquivo `.env`, você define as chaves de segurança (que devem bater com o que está no seu `Caddyfile` ou Docker):

```env
MERCURE_URL=http://localhost:8000/.well-known/mercure
MERCURE_PUBLIC_URL=http://localhost:8000/.well-known/mercure
MERCURE_PUBLISHER_JWT_KEY=!ChangeThisMercureHubJWTSecretKey!
```

> [!IMPORTANT]
> Em produção, certifique-se de trocar a chave JWT por uma string longa e aleatória para impedir que pessoas mal-intencionadas disparem eventos falsos no seu sistema.
