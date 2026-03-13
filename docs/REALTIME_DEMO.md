# Guia: Avisos em Tempo Real (Scaffold)

Este documento explica como utilizar e testar o sistema de **Avisos em Tempo Real** gerado automaticamente pelo comando `setup:aviso`.

---

## 🚀 Como instalar e rodar o exemplo

Se você ainda não gerou os arquivos, execute o comando no terminal:

```bash
php forge setup:aviso
```

### 1. Configuração do Ambiente (.env)
Para que as notificações funcionem corretamente em tempo real, o Driver de Sessão deve ser o **Redis**, garantindo que as sessões sejam compartilhadas entre os workers do FrankenPHP.

Edite seu arquivo `.env`:
```env
SESSION_DRIVER=redis
REDIS_HOST=redis
```

### 2. Rodar as Migrations
Crie a tabela `avisos` no banco de dados:
```bash
php forge migrate
```

### 3. Rebuild do Containers (Importante)
Como o uso do Redis e do Mercure exige extensões ativas e configuração de rede, recomendamos reiniciar o Docker caso tenha alterado algo no ambiente:
```bash
docker-compose up -d --build
```

---

## 🛠️ O que foi gerado?

O comando `setup:aviso` criou toda a estrutura necessária para um sistema reativo:

1.  **Migration**: Cria a tabela `avisos` (`texto`, `timestamps`).
2.  **Model**: `app/Models/Notice.php`.
3.  **Controller**: `app/Controllers/NoticeController.php` (com as rotas `/avisos` e `/avisos/lista`).
4.  **Views**: Localizadas em `app/Views/avisos/`:
    *   `index.php`: Página principal com o formulário HTMX.
    *   `partials/tabela.php`: A lista de avisos (renderizada de forma isolada).
    *   **Componente**: `app/Views/avisos/componentes/AvisosLista.php` (o coração do tempo real).

---

## 📡 Como a mágica acontece?

O sistema utiliza três tecnologias principais para evitar o F5:

### 1. Mercure (O Transmissor)
Quando você envia um novo aviso, o controller executa o helper `broadcast()`:
```php
broadcast('avisos-globais', ['mensagem' => 'Novo aviso!']);
```
Isso envia um "sinal" para o Hub Mercure avisando que algo mudou.

### 2. HTMX (O Atuador)
O componente frontend está configurado para "ouvir" esse sinal e fazer uma requisição AJAX automática:
```html
<div hx-get="/avisos/lista" hx-trigger="refresh-avisos from:body">
    <!-- Conteúdo atualizado aqui -->
</div>
```

### 3. Redis (O Sincronizador)
Ao rodar no modo **Worker** do FrankenPHP, o Redis garante que o estado da sua aplicação seja consistente entre todos os processos, evitando erros de leitura de dados de sessão e permitindo escalabilidade.

---

## 🧪 Testando
1. Abra `http://localhost:8000/avisos` em duas abas diferentes do navegador (ou em dispositivos diferentes).
2. Escreva um aviso em uma aba e clique em Enviar.
3. Observe a outra aba ser atualizada **instantaneamente** sem nenhum refresh de página.
