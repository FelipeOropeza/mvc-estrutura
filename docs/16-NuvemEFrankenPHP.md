# Nuvem e o Foguete FrankenPHP

Esse sistema foi inteiramente projetado para dar as costas aos servidores padrão limitados como o XAMPP, Apache e NGINX-FPM. O código aqui não vaza variáveis globais propositalmente.

Na raiz do seu projeto, temos um `Dockerfile` e um `docker-compose.yml`. Eles instalam a última versão do Golang Web Server (O Servidor Caddy com mod de PHP, o FrankenPHP).

## Worker Mode (Alta Performance)

O modo Worker mantém sua aplicação carregada na memória RAM, eliminando o custo de boot do PHP em cada requisição. No `public/index.php`, implementamos um loop de processamento com reciclagem automática:

```php
while ($running) {
    if (isset($_SERVER['FRANKENPHP_WORKER'])) {
        $running = \frankenphp_handle_request(function () use ($kernel) {
            $kernel->handle(Request::capture())->send();
        });
        if ($nbRequests++ >= 500) exit; // Recicla para evitar leaks
    } else {
        $kernel->handle(Request::capture())->send();
        $running = false;
    }
}
```

Isso reduz o tempo de resposta do framework de `~50ms` para cerca de **~2ms**, permitindo que sua aplicação suporte milhares de conexões simultâneas com baixo consumo de CPU.

## Vulcain & Early Hints (Preload)

Ativamos o suporte ao **Vulcain** no `Caddyfile`. Ele permite que o servidor envie "Early Hints" (Status 103) para o navegador. Isso diz ao browser quais arquivos CSS e JS ele deve começar a baixar **enquanto o PHP ainda está processando a lógica do banco de dados**. 

## Otimizações de Cache e Segurança

O seu `Caddyfile` agora gerencia automaticamente:
- **Cache de Assets**: Imagens, CSS e JS recebem headers de cache de 1 ano (`Cache-Control`), sendo servidos instantaneamente pelo navegador em visitas subsequentes.
- **Compressão Zstd**: Uma alternativa moderna e mais eficiente ao Gzip, reduzindo o tamanho dos arquivos transferidos sem pesar no processador.
- **Security Headers**: Inclusão nativa de headers de proteção contra Clickjacking (`X-Frame-Options`) e MIME Sniffing (`X-Content-Type-Options`).

## Mercure Hub Integrado (Real-time)

O FrankenPHP já vem com o **Mercure Hub** embutido via Caddy. Isso elimina a necessidade de serviços externos como Pusher para ter funcionalidades real-time. 

A configuração no `Caddyfile` permite que o servidor gerencie as conexões persistentes (SSE):
```caddy
mercure {
    publisher_jwt !ChangeThisMercureHubJWTSecretKey!
    subscriber_jwt !ChangeThisMercureHubJWTSecretKey!
    anonymous
    subscriptions
}
```

Combinado com o helper `broadcast()` no backend e HTMX no frontend, você consegue criar Chats, dashboards e notificações instantâneas com pouquíssimas linhas de código.
