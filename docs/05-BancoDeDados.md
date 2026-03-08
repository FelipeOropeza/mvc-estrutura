# Banco de Dados (ORM Moderno)

Suas Models em `app/Models` representam suas tabelas do DB e são turbinadas com um **Query Builder**.

## Buscas e Query Builder Fluente
Ao invés de programar SQL na mão, encadeie as instruções fluentemente.

```php
use App\Models\Produto;

$produtoModel = new Produto();

// Buscar todos ativados maiores que R$50.00
$produtosCaros = $produtoModel->select('nome, preco')
    ->where('ativo', '1')
    ->where('preco', '>', 50)
    ->orderBy('preco', 'DESC')
    ->limit(10)
    ->get(); // $produtosCaros é um Array de instâncias [Produto]

// Pegar APENAS UM registro
$meuArroz = $produtoModel->where('nome', '=', 'Arroz')->first();
echo $meuArroz->preco;
```

## Lógica de Join
Use Inner ou Left Joins sem precisar escrever uma linha de SQL crua:
```php
$produtosComCategoria = $produtoModel->select('produtos.*, categorias.titulo')
    ->join('categorias', 'categorias.id = produtos.categoria_id', 'INNER')
    ->get();
```

## Relacionamentos de Model Mágicos
Se o sistema for complexo, crie o relacionamento direto na Model. 

**Model Produto (Ele Pertence a uma Categoria):**
```php
class Produto extends Model {
    public function categoria(): ?Categoria {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }
}
```
**Model Categoria (Ela possui Vários Produtos):**
```php
class Categoria extends Model {
    public function produtos(): array {
        return $this->hasMany(Produto::class, 'categoria_id');
    }
}
```
**Como usar as Relações Pelo Controller:**
```php
$cat = (new Categoria())->find(1);
$todosOsProdutosDessaCategoria = $cat->produtos(); // Mágico!

$prod = (new Produto())->find(10);
echo $prod->categoria()->titulo; // Traz o registro dono do produto
```

## Mass Assignment e Proteção
No seu Model, a propriedade `$fillable` protege contra ataques hackers tentando injetar colunas como `nivel_admin = 1` pelo inspecionar do navegador. O Insert blindará os dados indesejados automaticamente:
```php
class User extends Model {
    protected array $fillable = ['email', 'password'];
}
```

## Contagem de Registros (Eficiência)
Evite trazer todos os registros do banco apenas para contar (`count($model->all())`). Use o método nativo que executa um `SELECT COUNT(*)` diretamente no SQL:

```php
$totalProdutos = (new Produto())->count();

// Contagem filtrada
$ativos = (new Produto())->where('ativo', '1')->count();

// Contagem em joins
$totalComCategoria = (new Produto())
    ->join('categorias', 'categorias.id = produtos.categoria_id')
    ->count();
```
