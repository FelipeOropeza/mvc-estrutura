# Cache e Sessões em Alta Velocidade (Redis)

O ecossistema é otimizado para não sofrer gargalos na leitura do Disco HD em Máquinas de Produção na Nuvem e pode ser configurado para usar o cluster ultra veloz baseando-se em RAM através do **Redis**.

Acesse seu arquivo base de configuração do `config/app.php` e altere a ponte de Sessão PHP.
```php
'session' => [
    'driver' => 'redis', // Pode ser 'file' para local ou 'redis' pro Heroku/Aws
    ...
]
```

O `RedisSessionHandler` já construído na nossa base se aliança imediatamente ao container sem vazar dados. Além disso, o GC Probability do PHP não sofre com interrupções nem File System Locking, protegendo seu Worker Assíncrono para aguentar milhares de concorrências simultâneas de usuários sem congelar o Servidor!
