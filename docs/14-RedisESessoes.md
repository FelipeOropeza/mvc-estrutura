# Cache e Sessões em Alta Velocidade (Redis)

O ecossistema é otimizado para não sofrer gargalos na leitura do Disco HD em Máquinas de Produção na Nuvem e pode ser configurado para usar o cluster ultra veloz baseando-se em RAM através do **Redis**.

Acesse seu arquivo base de configuração do `.env` na raiz do projeto e altere o driver de Sessão PHP, informando também (se necessário) as portas e host do seu servidor Redis.

```env
SESSION_DRIVER=redis

REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=senha_se_houver
```

O `RedisSessionHandler` já construído na nossa base se aliança imediatamente lendo suas variáveis de ambiente sem vazar dados. Além disso, o GC Probability do PHP não sofre com interrupções nem File System Locking, protegendo seu Worker Assíncrono para aguentar milhares de concorrências simultâneas de usuários sem congelar o Servidor! Se o seu servidor Redis cair, o Container também possui uma trava de segurança que imediatamente joga pro "File System Session" (temporário) para o App não despencar pra todos os usuários!
