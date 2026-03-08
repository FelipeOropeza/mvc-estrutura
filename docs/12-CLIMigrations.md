# CLI (Forge Console) e Migrations

Escrever código na mão é amadorismo. O `php forge` é um gerador visual super útil acessível de raiz no micro-framework!

**Lista Rápida Baseada no Motor Padrão:**
```bash
# Entenda as capacidades disponíveis no App Local:
php forge

# Geradores Rápidos - "Make":
php forge make:controller UsuarioController  # Na Pasta /Controllers
php forge make:model Fornecedor             # Na Pasta /Models com $table pronto
php forge make:service EmailService         # Na Pasta /Services pra lógica de regra de negócio
php forge make:middleware TravaIP           # Na Pasta /Middleware 
php forge make:view relatorios/financeiro   # Gera HTMLs limpos e alinhados num padrão

# Criação de Lógicas Magicas Injetáveis na DTO e Model
php forge make:rule NomeDaSuaValidadora      # Pasta /Rules
php forge make:mutator NomeDaSuaMutaçãoLimpeza # Pasta /Mutators

# Compiladores Finais e Scaffolding
php forge setup:auth               # Instala um sistema MVC de Autenticação base (Login, Registro, DB e Rotas)
php forge setup:engine twig        # Migra o projeto entre Php/Twig como View padrão do Front   
php forge optimize                 # Compila Rotas e Arquivos no Cache acelerando em até 10x
php forge optimize:clear           # Limpa a compilação do Cache e do Optimize 

# Ambiente de Desenvolvimento:
php forge serve                    # Inicia o servidor local inteligente com logs limpos
php forge serve --port=9000        # Inicia em uma porta específica
php forge serve --debug           # Inicia exibindo os logs detalhados do PHP
```

## Migrations e Schema Builder

O `php forge migrate` conta com um sistema brilhante de Versionamento de Banco de Dados. Nunca crie tabelas abrindo o PHPMyAdmin na sua máquina. Crie um arquivo declarativo que rastreia tudo e rola pelos ambientes do seu time!

Para criar um novo bloco cronológico pro banco:
```bash
php forge make:migration CreateUsersTable
```

A Migration utilizará nossa classe interna conectada ao Database de alta performance, utilizando um poderoso Schema Builder orientado a objetos.

Exemplo de Escrita no Scaffold:
```php
<?php

use Core\Database\Schema\Schema;
use Core\Database\Schema\Blueprint;

class CreateUsersTable
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamps(); // Cria as colunas created_at e updated_at mágicas
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
}
```

Quando rolar `php forge migrate`, o framework lê e executa linha por linha apenas os arquivos ordenados por Timestamp que ainda não foram gravados com sucesso na sua base! Em caso de falha em nuvem ele executa rollback do ambiente em andamento!
