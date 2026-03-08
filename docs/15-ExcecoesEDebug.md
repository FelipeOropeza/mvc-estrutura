# Tratamento de Exceções e Debug

O framework intercepta **todas** as exceções e erros não tratados e os converte em respostas HTTP elegantes, prevenindo que detalhes internos vaze para o usuário em produção.

---

## Modos de Debug

Configure no seu `.env`:

```env
APP_DEBUG=true   # Desenvolvimento: mostra tela detalhada
APP_DEBUG=false  # Produção: mostra página genérica e loga silenciosamente
```

### Modo Desenvolvimento (`APP_DEBUG=true`)

Exibe uma **Tela de Erro em Dark Mode** com:
- Tipo da Exception e mensagem completa
- Arquivo e linha exatos onde ocorreu
- Stack Trace formatado com syntax highlight

### Modo Produção (`APP_DEBUG=false`)

- Exibe uma página genérica e amigável (`500 — Algo deu errado`)
- Salva o erro completo em `storage/logs/app.log` para investigação posterior
- **Nunca expõe detalhes internos** ao usuário

---

## Suporte a HTMX

Se um erro ocorrer durante uma requisição HTMX, o Handler automaticamente adiciona os headers:
```
HX-Retarget: body
HX-Reswap: innerHTML
```
Isso garante que a tela de erro apareça **em tela cheia** e não espremida dentro de um componente da interface.

---

## Erros HTTP com `abort()`

Para retornar erros HTTP explícitos em qualquer ponto da aplicação:

```php
// Em Controllers, Middlewares ou Services:
abort(404);                             // Não encontrado
abort(403, 'Você não tem permissão.');  // Acesso negado
abort(401, 'Faça login primeiro.');     // Não autenticado
abort(500, 'Serviço indisponível.');
```

O Handler renderiza a página de erro correta automaticamente — em HTML para web ou JSON para APIs (`Accept: application/json`).

---

## Erros de Validação (`ValidationException`)

São tratados separadamente dos erros do sistema:

- **Web:** Redireciona de volta com os erros em flash session (acessados via `errors()` e `old()`)
- **API:** Retorna `422 Unprocessable Entity` com JSON:

```json
{
  "status": "error",
  "message": "Erro de Validação Atributiva",
  "errors": {
    "email": ["Digite um E-mail válido."],
    "senha": ["Mínimo de 8 caracteres."]
  }
}
```

---

## Respostas para APIs vs Web

O Handler detecta automaticamente o tipo de resposta pelo header `Accept` ou pela URL:
- `Accept: application/json` → resposta JSON
- URL começa com `/api/` → resposta JSON
- Outros casos → resposta HTML

---

## Logs de Erro

```php
// Gravar no log manualmente em qualquer ponto:
logger()->info('Usuário fez login', ['id' => $userId]);
logger()->error('Falha ao processar pagamento', [
    'erro'     => $e->getMessage(),
    'pedido'   => $pedidoId,
]);
```

Os logs ficam em `storage/logs/app.log` com timestamp e contexto.
