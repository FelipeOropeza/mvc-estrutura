# Documentação Oficial: MVC Base Framework

Bem-vindo ao manual completo do **MVC Base**, um micro-framework ultra-rápido, arquitetado em conceitos modernos (Stateless, PSR-11 e PSR-15 concept) preparado para o FrankenPHP. Aqui você aprenderá a dominar cada engrenagem para construir desde blogs até e-commerces e sistemas complexos.

---

## Índice

1. [O Ciclo de Vida da Requisição (Lifecycle)](#1-o-ciclo-de-vida-da-requisição-lifecycle)
2. [Estrutura de Diretórios](02-EstruturaDeDiretorios.md)
3. [Roteamento Avançado](03-RoteamentoAvancado.md)
4. [Controllers e Regras de Négocio (Services)](04-ControllersEServices.md)
5. [Banco de Dados (ORM Moderno)](05-BancoDeDados.md)
6. [Validações e Data Transfer Objects (DTOs)](06-ValidacoesEDtos.md)
7. [Mutations (Mutadores de Dados)](07-Mutations.md)
8. [Middlewares e Segurança](08-MiddlewaresESeguranca.md)
9. [Upload de Arquivos](09-UploadDeArquivos.md)
10. [Views e Interface de Usuário (UI)](10-ViewsEUI.md)
11. [Injeção de Dependências e Service Providers](11-InjecaoDeDependencias.md)
12. [CLI (Forge Console) e Migrations](12-CLIMigrations.md)
13. [Helpers Globais](13-HelpersGlobais.md)
14. [Cache e Sessões em Alta Velocidade (Redis)](14-RedisESessoes.md)
15. [Tratamento de Exceções e Debug Bar](15-ExcecoesEDebug.md)
16. [Nuvem e o Foguete FrankenPHP](16-NuvemEFrankenPHP.md)
17. [JWT & API Stateless (Mobile/SPA)](17-JWT-E-API.md)
18. [Sistema de E-mails (PHPMailer)](18-Emails.md)
19. [Filas e Jobs Assíncronos](19-Filas-E-Jobs.md)
20. [Sistema de Cache (File/Redis)](20-Cache.md)

---

## 1. O Ciclo de Vida da Requisição (Lifecycle)

Entender o caminho que a informação faz desde que o usuário aperta "Enter" até a tela aparecer é o segredo dos desenvolvedores Sêniors. Como este framework é baseado em padrões modernos (PSR-15), o fluxo usa uma arquitetura de "Cebola" (Onion Architecture).

> ![Ciclo de Vida da Requisição (Lifecycle)](assets/lifecycle.png)
