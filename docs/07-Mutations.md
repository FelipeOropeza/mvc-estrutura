# Mutations (Mutadores de Dados)

Permitem converter/sanitizar/criptografar silenciosamente um dado capturado de formulário ANTES de enviá-lo de fato ao Banco, usando também atributos mágicos do PHP8.

## O Mutator Nativo: Criptografia de Senhas
Na sua Model User:
```php
use App\Models\Model;
use Core\Attributes\Hash; // Usa password_hash no preenchimento

class User extends Model {
    #[Hash]    
    public ?string $password = null; 
    // Quando você salvar Rato123 na model e chamar O insert($data), ele vai salvar $2b$10$xyz no banco mágico sozinho.
}
```

## Criando Seu Próprio Mutator (Exemplo de limpeza de pontos do CPF)
Use o comando de motor CLI (Forge):
```bash
php forge make:mutator LimpaCpf
```
Entre na lógica `app/Mutators/LimpaCpf.php` configurando a regex e instale-a assim na Model:
```php
use App\Mutators\LimpaCpf;

class Fornecedor extends Model {
    #[Required]
    #[LimpaCpf] 
    // Ele vai pegar "124.550.212-00" limpo pra "12455021200" e encaminhar como string final pro insert().
    public ?string $cpf = null; 
}
```
