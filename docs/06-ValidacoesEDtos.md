# Validações e Data Transfer Objects (DTOs)

Ao contrário dos padrões antigos, as validações não devem poluir o fluxo dos `Controllers`. Também já evoluímos o pensamento moderno: a classe `Model` só deve se preocupar com abstrair as interações do banco de dados (Query Builder, Limits) e lidar com Mutators (Formatações para insert), sem ficar lidando com regras de formulário HTTP.

As suas validações de Requests vivem exclusivamente e de maneira poderosa dentro dos **Data Transfer Objects (DTOs)**, atuando como verdadeiros **Gatekeepers**, blindando sua aplicação usando a notação mágica do **PHP 8 Attributes**.

## Regras Mágicas e o Conceito Gatekeeper

Mapeie exatamente o que a sua Rota espera que o usuário envie em JSON ou num `POST` de formulário criando uma classe na pasta `app/DTOs`.

```php
namespace App\DTOs;

use Core\Validation\DataTransferObject;
use Core\Attributes\Required;
use Core\Attributes\Min;
use Core\Attributes\Email;
use Core\Attributes\MatchField;

class RegistroDTO extends DataTransferObject
{
    // Required garante que não seja vazio e aceita customizar o texto 
    #[Required('Digite um E-mail')]
    #[Email('Esse não parece ser um E-mail válido.')]
    public ?string $email = null;

    // Número Mínimo/Máximo de Caracteres, Valor Numérico ou Elementos de um Array
    #[Required]
    #[Min(8, 'Precisamos que a senha tenha no mínimo 8 dígitos, pra sua segurança')]
    public ?string $password = null;

    // Valida se a Confirmação de Senha é igual à Senha
    #[MatchField('password', 'As senhas não conferem')]
    public ?string $password_confirm = null;

    // Garante que o valor seja único no Banco de Dados
    // Útil para e-mails, usernames ou CPFs
    #[Required]
    #[Unique(table: 'usuarios', column: 'email', message: 'Este e-mail já está em uso.')]
    public ?string $email_cadastro = null;

    // Para EDIÇÃO: ignore o ID do próprio registro sendo editado
    #[Unique(table: 'usuarios', column: 'email', ignore: 'id')]
    public ?string $email_edicao = null;

    // (Opcional) Retorne false para barrar a requisição com 403 antes mesmo 
    // das regras passarem! Ideal para checar cargo/ACL
    protected function authorize(): bool {
        return session()->get('cargo') !== 'suspenso';
    }
}
```

## Mutators (Transformações Automáticas)

Além de validar, os DTOs e Models podem **transformar** os dados antes mesmo de você tocá-los. O caso de uso mais comum é criptografar senhas automaticamente:

```php
use Core\Attributes\Hash;
use Core\Attributes\Required;

class UsuarioDTO extends DataTransferObject 
{
    #[Required]
    public string $name;

    #[Required]
    #[Hash] // Criptografa a string usando password_hash() automaticamente!
    public string $password;
}
```

## A Mágica no Controller (Autowiring e Defesa Automática)

Sua interface exige a `RegistroDTO` como parâmetro e o Container faz a injeção cruzada na rota com os disparos via Reflection `Request`. Se as regras de atributos falharem, o Controller **nem chega a ser iniciado**. O usuário recebe automaticamente o *Redirect* de erro com as Flash messages pra interface. O Controller agora só trabalha em paz!

```php
use App\DTOs\RegistroDTO;
use Core\Http\Response;

// O método SÓ executa se nenhum invasor passar o gatekeeper da DTO!
public function registrar(RegistroDTO $dto)
{
    // O $dto agora guarda apenas campos limpos, testados e super tipados!
    $dadosLimpos = $dto->toArray();
    
    // Pode remover chaves não relativas à tabela SQL, como a confirmação
    unset($dadosLimpos['password_confirm']);
    
    $userModel = new Usuario();
    $userModel->insert($dadosLimpos);
    
    return Response::makeRedirect('/sucesso');
}
```

## Validação Manual para Casos Específicos

Quando algo validou no DTO mas só falhou lá no final da operação lógica isolada (Ex: processar Cartão na Pagar.me devolveu que não tem limite), use o método manual relâmpago:

```php
$pagou = $pagarMe->transacionar($cartao);
if (!$pagou) {
    fail_validation('cartao_credito', 'Limite Recusado pelo seu Banco Emissor.');
    // Isso cancela a rota automaticamente, salva a variável de sessão e volta a tela avisando onde ocorreu o erro.
}
```

## Validação Nativa em Models (Active Record Style)

Se você prefere validar os dados diretamente no Model (útil para processos que não passam por DTOs ou em scripts customizados), o formalismo de Atributos também funciona lá! Basta usar propriedades públicas no Model:

```php
class Produto extends Model {
    #[Required('O nome é obrigatório')]
    public string $nome;

    #[Required]
    #[IsFloat]
    public float $preco;
}

// No seu Controller ou Script:
$produto = new Produto();
try {
    $dadosValidos = $produto->validate($_POST);
    $produto->insert($dadosValidos);
} catch (ValidationException $e) {
    // Tratamento automático ou manual
}
```

## Criando suas Próprias Regras!
Use a Forja do Console (CLI):
```bash
php forge make:rule DocumentoCpf
```
Edite a Lógica (`app/Rules/DocumentoCpf.php`) para testar DB, Matemática, Regex. Depois, simplesmente insira o novo atributo na `class RegistroDTO`: `#[DocumentoCpf]`.

---

## ⚠️ Importante: Escape de HTML pertence à View, não ao Validator

O Validator **não** aplica `htmlspecialchars()` nos dados. Os dados chegam ao banco e às respostas de API exatamente como foram enviados (sem modificação).

O escape deve acontecer **na saída** — ou seja, nas suas Views, usando o helper `e()`:

```php
// ✅ CORRETO — escapa na View, na hora de exibir:
<p><?= e($produto->nome) ?></p>
<input value="<?= e(old('nome')) ?>">

// ❌ ERRADO — dados sanitizados antes de salvar causam double escaping:
// "Café & Pão" → "Caf&amp;é &amp;amp; P&amp;ão" (duplicado!)
```

Isso garante que APIs, emails e PDFs recebam os dados originais sem caracteres HTML indesejados.

