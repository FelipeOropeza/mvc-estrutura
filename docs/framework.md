# Documentação Oficial: MVC Base Framework

Bem-vindo ao manual completo do **MVC Base**, um micro-framework ultra-rápido, arquitetado em conceitos modernos (Stateless, PSR-11 e PSR-15 concept) preparado para o FrankenPHP. Aqui você aprenderá a dominar cada engrenagem para construir desde blogs até e-commerces e sistemas complexos.

---

## Índice

### 🔰 Essencial (Para começar)
* [Estrutura de Diretórios](02-EstruturaDeDiretorios.md)
* [Roteamento Avançado & Atributos PHP 8](03-RoteamentoAvancado.md)
* [Controllers e Regras de Négocio (Services)](04-ControllersEServices.md)
* [Views e Interface de Usuário (UI)](10-ViewsEUI.md)
* [Banco de Dados (ORM Moderno)](05-BancoDeDados.md)
* [Tutorial Prático: Criando meu primeiro CRUD](22-TutorialCRUD.md)

### 🛠 Intermediário (Para evoluir)
* [O Ciclo de Vida da Requisição (Lifecycle)](#1-o-ciclo-de-vida-da-requisição-lifecycle)
* [Validações e Data Transfer Objects (DTOs)](06-ValidacoesEDtos.md)
* [Mutations (Mutadores de Dados)](07-Mutations.md)
* [Middlewares e Segurança](08-MiddlewaresESeguranca.md)
* [Tratamento de Exceções e Debug Bar](15-ExcecoesEDebug.md)
* [Upload de Arquivos](09-UploadDeArquivos.md)
* [Helpers Globais](13-HelpersGlobais.md)

### 🚀 Avançado (Domine a Arquitetura)
* [Injeção de Dependências e Service Providers](11-InjecaoDeDependencias.md)
* [CLI (Forge Console) e Migrations](12-CLIMigrations.md)
* [Cache e Sessões em Alta Velocidade (Redis)](14-RedisESessoes.md)
* [Nuvem e o Foguete FrankenPHP](16-NuvemEFrankenPHP.md)
* [JWT & API Stateless (Mobile/SPA)](17-JWT-E-API.md)
* [Sistema de E-mails (PHPMailer)](18-Emails.md)
* [Filas e Jobs Assíncronos](19-Filas-E-Jobs.md)
* [Sistema de Cache (File/Redis)](20-Cache.md)
* [Eventos em Tempo Real (Mercure)](21-MercureRealTime.md)

---

## 1. O Ciclo de Vida da Requisição (Lifecycle)

Entender o caminho que a informação faz desde que o usuário aperta "Enter" até a tela aparecer é o segredo dos desenvolvedores Sêniors. Como este framework é baseado em padrões modernos (PSR-15), o fluxo usa uma arquitetura de "Cebola" (Onion Architecture).

> ![Ciclo de Vida da Requisição (Lifecycle)](assets/lifecycle.png)
