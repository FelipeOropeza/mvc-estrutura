# Criando o primeiro CRUD

Este documento está reservado para o tutorial passo a passo de inicialização rápida com o Framework.
Nele aprenderemos:
1. Criar Migration e o Model (`php forge make:model Produto -m`)
2. Criar o Controller (`php forge make:controller ProdutoController`)
3. Definir as rotas usando Atributos PHP 8 (`#[Get('/produtos')]`)
4. Criar as Views em `resources/views/produtos/`
5. Salvar e validar o input via `$request->validate()`
