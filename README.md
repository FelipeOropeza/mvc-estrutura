# MVC Base em PHP Puro

Um esqueleto simples e leve para estrutura MVC completa em PHP, pronto para usar com Composer, contendo: Router próprio, Request DTO validator, View Engine simplificada e utilitários da CLI (Forge).

## Documentação

Para mergulhar fundo e aprender a separar a lógica da sua aplicação de forma profissional num MVC, construir modelos, usar o Validator baseado em PHP 8 Attributes e a CLI do Framework, consulte a documentação dedicada na pasta `docs/`:

=> [Ler a Documentação do Motor MVC](docs/framework.md)

---

## Início Rápido (Instalação e Teste)

### Método 1: Via Composer (Recomendado)
A forma mais fácil de criar a aplicação é rodar o `create-project`. Ele baixará a última versão, iniciará o **instalador interativo** e limpará os arquivos de instalação ao finalizar.

```bash
composer create-project felipe-code/mvc-base nome-do-seu-projeto
```

### Método 2: Via Git Clone Manual
Se preferir clonar o repositório, você pode engatilhar o instalador interativo logo em seguida com os comandos abaixo:

```bash
git clone https://github.com/FelipeOropeza/mvc-estrutura.git meu-app
cd meu-app
composer install
composer run post-create-project-cmd
```

### Iniciando o Servidor Local:
Uma vez que o projeto esteja instanciado, inicie o servidor interno de prateleira:
```bash
composer start
```
E acesse `http://localhost:8000` no seu navegador.

### Comandos Rápidos da CLI:
```bash
php forge make:controller NomeController
php forge make:model TabelaModel
```

## Licença

MIT
