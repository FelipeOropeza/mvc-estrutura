# MVC Base em PHP Puro

Um esqueleto simples e leve para estrutura MVC completa em PHP, pronto para usar com Composer, contendo: Router próprio, Request DTO validator, View Engine simplificada e utilitários da CLI (Forge).

## Documentação

Para mergulhar fundo e aprender a separar a lógica da sua aplicação de forma profissional num MVC, construir modelos, usar o Validator baseado em PHP 8 Attributes e a CLI do Framework, consulte a documentação dedicada na pasta `docs/`:

=> [Ler a Documentação do Motor MVC](docs/framework.md)

---

## Início Rápido (Instalação e Teste)

Criar o projeto a partir do esqueleto:
```bash
composer create-project felipe-code/mvc-base nome-do-seu-projeto
```

Iniciar o Servidor Local Embutido:
```bash
cd nome-do-seu-projeto
composer start
```
Acesse `http://localhost:8000` na sua máquina.

### Comandos Rápidos da CLI:
```bash
php forge make:controller NomeController
php forge make:model TabelaModel
```

## Licença

MIT
