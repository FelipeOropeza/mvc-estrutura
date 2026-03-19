# Controllers, HTTP e Services

O Controller nunca deve dar um "echo". Ele sempre __retorna__ uma Resposta.

**Devolvendo JSON (Para APIs) ou Redirecionando:**
> **Dica:** Você pode gerar um Controller focado em API com todos os métodos CRUD preparados rodando `php forge make:controller Api/MeuController --api`
```php
namespace App\Controllers;

use Core\Http\Request;
use Core\Http\Response;

class ApiController 
{
    public function obterItens()
    {
        return Response::makeJson(['status' => 'sucesso', 'data' => [1, 2, 3]]);
    }
    
    public function salvar(Request $request)
    {
        // Se der sucesso... redirecione de volta para o menu:
        return Response::makeRedirect('/menu-principal');
    }
}
```

**Voltando atrás em formulários (Back):**
Muitas vezes você quer devolver o usuário para a última tela que ele estava.
```php
return Response::makeRedirectBack();
```

## Services (Regras de Negócio)

Ao longo do desenvolvimento, Controllers tendem a acumular responsabilidades como integrações pesadas de API, processamento complexo de frete ou geração de Notas Fiscais. Para evitar Controllers obesos com lógicas de negócio cruas, utilizamos a **Camada de Services**. O Framework injeta essas classes magicamente pelo *IoC Container* do Núcleo.

**Exemplo de uma Rule de Negócios Independente:**
(Crie uma classe pura em `app/Services/PagamentoService.php`)
```php
namespace App\Services;

class PagamentoService 
{
    public function processarViaPagarMe(array $dadosCartao, float $valor) 
    {
        // Toda a sua integração complexa de rede vem aqui...
        // Sem sujar o Controller
        return true; 
    }
}
```

**Uso no Controller (Autowiring em Ação):**
Basta solicitar a classe que quer usar nos parâmetros do Controlador, o Framework a constrói e resolve todas as suas dependências sem termos que usar `new PagamentoService()`!
```php
use App\Services\PagamentoService;
use Core\Http\Request;
use Core\Http\Response;

public function checkout(Request $request, PagamentoService $service) 
{
    $sucesso = $service->processarViaPagarMe($request->all(), 500.00);
    
    if (!$sucesso) {
        fail_validation('cartao', 'Cartão recusado pelo banco emissor.');
    }
    
    return Response::makeRedirect('/painel');
}
```
Isso mantém sua aplicação escalável, perfeitamente testável e seguindo os rígidos princípios _SOLID_.
