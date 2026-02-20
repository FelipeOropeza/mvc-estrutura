# Documentação do Framework MVC Base

## 1. Estrutura de Diretórios

- **app/Controllers**: Aqui ficam seus Controladores, responsáveis por receber as requisições HTTP e orquestrar a lógica.
- **app/Models**: Classes que representam as tabelas do seu banco de dados ou a regra de negócios pura.
- **app/Requests**: Objetos de Transferência (DTOs) que representam os dados enviados pelo cliente para os controladores. São úteis para aplicar validações automáticas.
- **app/Views**: Os arquivos HTML e de interface que serão renderizados para o usuário (suporta PHP puro estilo Blade ou Twig).
- **config**: Contém as configurações de Banco de Dados, visualização e outros padrões.
- **core**: O motor interno do Framework. Aqui você encontra as classes base de Controller, Roteador, Validação, Resposta HTTP, etc. Não deve ser alterado durante o desenvolvimento normal.
- **public**: O ponto de entrada da aplicação (`index.php`), bem como o local ideal para arquivos CSS, JS, e Imagens públicas.
- **routes**: As definições de URLs da sua aplicação (`web.php`).

## 2. Ferramenta CLI (Forge)

Assim como frameworks maiores possuem ferramentas integradas para criar arquivos, este motor expõe a linha de comando `forge` na raiz do projeto.

Para utilizar no terminal:
```bash
php forge make:controller UsuarioController
php forge make:model Produto
php forge make:view produto/lista
```
*(Se estiver no Windows ou possuir o executável configurado nas variáveis de ambiente, apenas chamar `forge` pode ser suficiente)*.

Isso acelera o preenchimento inicial de *boilerplates* (arquivos com código repetitivo de estrutura).

## 3. Sistema de Validação Dinâmica com PHP 8 Attributes

Uma das joias deste micro-framework é o seu sistema de Validação de Requests inspirado nos frameworks modernos e linguagens tipadas.

### Criando a Classe Request
Em vez de poluir o `Controller` com um monte de condicional de `if($_POST['email'] == null)`, nós declaramos uma classe que representa o "Contrato" de dados com Atributos PHP de validação.

```php
namespace App\Requests;

use Core\Attributes\Required;
use Core\Attributes\Email;
use Core\Attributes\MinLength;

class CadastroRequest
{
    #[Required(message: "Por favor, preencha o seu nome.")]
    public ?string $nome = null;

    #[Required]
    #[Email(message: "E-mail com formato inválido.")]
    public ?string $email = null;

    #[Required]
    #[MinLength(8)]
    public ?string $senha = null;
}
```

### Usando a Validação no Controller
Basta instanciar e chamar o helper global `validate()`.
A magia do `validate()` é não precisar se preocupar com os casos de falha. Se algum dos dados da Request atual estiver errado, ele **encerra a execução e redireciona automaticamente o usuário de volta**, injetando as mensagens de erro na sessão para exibição no seu HTML (Sessões Flash).

```php
public function store()
{
    // O Framework lida com falhas automaticamente aqui.
    $dadosValidados = validate(new \App\Requests\CadastroRequest());

    // Se chegar até aqui, os dados estão perfeitos para seguir no BD!
    $model = new \App\Models\User();
    $model->save($dadosValidados);

    return response()->redirect('/sucesso');
}
```

### Recuperando os erros na View
Se um redirecionamento de erro ocorreu, use o helper global `errors('campo')` e `old('campo')` nos seus campos.

```html
<input type="text" name="nome" value="<?= old('nome') ?>">
<span class="erro"><?= errors('nome') ?></span>
```

## 4. Helpers Globais Úteis

- `request()`: Acessa a classe e o array completo do Request (`$_POST`, `$_GET`, etc).
- `response()`: Usada para gerenciar os redirecionamentos ou envios de cabeçalhos (`response()->json($dados)`).
- `view('nome_arquivo', [])`: Renderiza um HTML final ou view mapeada da pasta `app/Views/` imediatamente passando as variáveis em um array.
