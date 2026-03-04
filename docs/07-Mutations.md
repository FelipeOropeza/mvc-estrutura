# Mutations (Mutadores de Dados)

Permitem converter/sanitizar/criptografar silenciosamente um dado recebido do formulário antes de ele seguir o fluxo para a camada de Serviços ou Model, usando os atributos mágicos do PHP8.

Seguindo a mesma lógica das validações, os **Mutators brilham intensamente quando aplicados nas suas DTOs**. Eles "preparam" os dados limpando sujeiras logo na porta de entrada da requisição.

## O Mutator Nativo: Criptografia de Senhas
No seu DTO de Registro:
```php
namespace App\DTOs;

use Core\Validation\DataTransferObject;
use Core\Attributes\Hash; // Aplica o password_hash
use Core\Attributes\Required;

class AdicionaUsuarioDTO extends DataTransferObject 
{
    #[Required]
    #[Hash]    
    public ?string $password = null; 
    // Quando o usuário submeter 'Rato123', ao chegar no Controller o hash '$2b$10...' 
    // já estará limpo e criptografado dentro dessa propriedade para você salvar com segurança.
}
```

## Criando Seu Próprio Mutator (Exemplo: Limpeza de pontos do CPF)
Use o comando de motor CLI (Forge):
```bash
php forge make:mutator LimpaCpf
```
Edite a lógica da regex em `app/Mutators/LimpaCpf.php`. Depois, basta plugar a anotação na sua DTO:
```php
namespace App\DTOs;

use Core\Validation\DataTransferObject;
use App\Mutators\LimpaCpf;
use Core\Attributes\Required;

class CompraContratoDTO extends DataTransferObject 
{
    #[Required]
    #[LimpaCpf] 
    // A propriedade intercepta formatações indesejadas na Raiz do Request.
    // '124.550.212-00' virará silenciosamente '12455021200'
    public ?string $cpf = null; 
}
```

**Por que na DTO e não na Model?**
Se você colocar o Mutator na Model, o seu Controller ainda vai trabalhar com a String `124.550.212-00` suja temporariamente antes de fazer o `$model->insert()`. Quando você anexa o mutator (e a validação) **direto na DTO**, a sua regra de negócio no Controller e nos `Services` nunca precisa se preocupar em limpar formatadores. O Controller receberá 100% dos dados já purificados e tratados!
