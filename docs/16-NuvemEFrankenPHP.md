# Nuvem e o Foguete FrankenPHP

Esse sistema foi inteiramente projetado para dar as costas aos servidores padrão limitados como o XAMPP, Apache e NGINX-FPM. O código aqui não vaza variáveis globais propositalmente.

Na raiz do seu projeto, temos um `Dockerfile` e um `docker-compose.yml`. Eles instalam a última versão do Golang Web Server (O Servidor Caddy com mod de PHP, o FrankenPHP).

## Worker Mode Limitless
Se você fizer deploy num Render, Railway, AWS ou Hostinger Docker e olhar o finalzinho do `index.php`, notará o loop infinito acoplado:
```php
if (isset($_SERVER['FRANKENPHP_WORKER'])) {
    while (frankenphp_handle_request('core_run')) {
        // ... Request é resetada a cada LOOP, o APP Nunca desliga da memória!
    }
} else {
    core_run(); // Modo Apache Normal
}
```

Isso significa que o banco de dados e os controllers só iniciam **uma única vez** (durante o Boot da Máquina no host) e ficam quentes esperando o usuário de braços abertos num Loop infinito em Memória RAM, baixando a casa de requisições do seu App de `~50ms` para insanos e absurdos **~2ms** na resposta final de Database! Desfrute desse salto de performance imbatível do PHP moderno!

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
