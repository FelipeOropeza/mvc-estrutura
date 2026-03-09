# Plano de Evolução do Framework - Versão "Universal"

Este plano descreve a implementação das funcionalidades necessárias para transformar o framework atual em uma ferramenta capaz de lidar com projetos de produção reais, desde aplicações web tradicionais até APIs para Mobile e processamento pesado em segundo plano.

## 🎯 Critérios de Sucesso
- [ ] **Autenticação de API**: Possibilidade de autenticar via bearer tokens (JWT).
- [ ] **Sistema de E-mail**: Envio de e-mails usando templates e drivers plugáveis.
- [ ] **Filas (Queues)**: Processamento assíncrono com suporte a Banco de Dados e Redis.
- [ ] **Cache Moderno**: Abstração de cache com drivers configuráveis.
- [ ] **Storage Flexível**: Suporte a diferentes adaptadores (Local/S3).

## 🛠️ Tech Stack Selecionada
- **Autenticação**: `firebase/php-jwt` para tokens seguros e leves.
- **E-mails**: `phpmailer/phpmailer` (estável e amplamente suportado).
- **Cache/Queue (Redis)**: `predis/predis` (já presente no composer.json).
- **Core**: PHP 8.0+ com arquitetura baseada em Services e Middlewares.

## 📂 Novas Estruturas de Arquivos (Previstas)
```text
core/
├── Auth/
│   ├── TokenManager.php        # Lógica de JWT
│   └── PersonalAccessToken.php # Model para tokens persistentes
├── Mail/
│   ├── MailerInterface.php
│   ├── MailManager.php         # Facade/Manager
│   └── Drivers/                # PHPMailerAdapter...
├── Queue/
│   ├── QueueInterface.php
│   ├── Job.php                 # Classe base
│   └── Drivers/                # DatabaseDriver, RedisDriver
└── Cache/
    └── CacheManager.php
```

## 📝 Lista de Tarefas (Roadmap)

### Fase 1: Fundação e Containers (Infra)
- [x] **Tarefa 1.1**: Adicionar serviço Redis ao `docker-compose.yml`.
- [x] **Tarefa 1.2**: Instalar dependências PHPMailer e PHP-JWT via Composer.
- [x] **Tarefa 1.3**: Configurar variáveis de ambiente no `.env` para Mail, JWT e Redis.

### Fase 2: API & Segurança (Tokens)
- [x] **Tarefa 2.1**: Criar `core/Auth/TokenManager.php` para codificar/decodificar JWT.
- [x] **Tarefa 2.2**: Criar stubs de scaffold de API em `core/Console/Templates/api`.
- [x] **Tarefa 2.3**: Implementar comando `php forge setup:api` para gerar estrutura completa (Controller, Model, DTO, Service, Middleware e Rotas).

### Fase 3: Comunicação (E-mail)
- [x] **Tarefa 3.1**: Criar abstração `core/Mail`.
- [x] **Tarefa 3.2**: Implementar `PHPMailerAdapter`.
- [x] **Tarefa 3.3**: Criar helper global `mail()` e suporte a templates Twig para e-mails.

### Fase 4: Escalabilidade (Queue/Jobs)
- [x] **Tarefa 4.1**: Criar estrutura de `Job` e `QueueManager`.
- [x] **Tarefa 4.2**: Implementar `DatabaseDriver` (versão nativa PHP).
- [x] **Tarefa 4.3**: Implementar `RedisDriver` (versão Docker).
- [x] **Tarefa 4.4**: Criar comando `php forge queue:work` para processar os jobs.

### Fase 5: Cache e Storage
- [x] **Tarefa 5.1**: Implementar `CacheManager` com drivers File e Redis.
- [ ] **Tarefa 5.2**: Refatorar `StorageManager` para aceitar configurações de discos.

---

## ✅ PHASE X: VERIFICAÇÃO FINAL
- [ ] Lint & Type Check: `npm run lint` (se houver) / Manual PHP lint.
- [ ] Security Scan: Verificar se segredos do JWT não estão hardcoded.
- [ ] Worker Test: Disparar 100 jobs e verificar processamento sem travamento.
- [ ] Email Test: Enviar e-mail de teste via SMTP real.
