# Banco de Dados (ORM Moderno)

Suas Models em `app/Models` representam suas tabelas do DB e são turbinadas com um **Query Builder** poderoso e fluente.

---

## Buscas e Query Builder Fluente

Ao invés de escrever SQL na mão, encadeie as instruções fluentemente:

```php
use App\Models\Produto;

$produtoModel = new Produto();

// Buscar todos ativados maiores que R$50.00
$produtosCaros = $produtoModel->select('nome, preco')
    ->where('ativo', '1')
    ->where('preco', '>', 50)
    ->orderBy('preco', 'DESC')
    ->limit(10)
    ->get(); // Array de instâncias [Produto]

// Pegar APENAS UM registro
$meuArroz = $produtoModel->where('nome', '=', 'Arroz')->first();
echo $meuArroz->preco;
```

---

## Busca por ID

```php
// Retorna null se não encontrar
$produto = (new Produto())->find(5);

// Lança 404 automaticamente se não existir (recomendado em Controllers)
$produto = (new Produto())->findOrFail(5);
```

---

## Condições OR

Use `orWhere()` para combinar condições com `OR`:

```php
// Busca produtos cujo nome OU descrição contenha "camisa"
$resultados = $produtoModel
    ->where('nome', 'LIKE', '%camisa%')
    ->orWhere('descricao', 'LIKE', '%camisa%')
    ->get();

// orWhereIn() para OR com lista de valores
$especiais = $produtoModel
    ->where('ativo', '1')
    ->orWhereIn('categoria_id', [2, 5, 8])
    ->get();
```

---

## Agrupamento de Condições (Filtros Avançados)

Para criar grupos de condições (como (A AND B) OR (C AND D)), você pode usar Closures no `where` ou `orWhere`:

```php
// SELECT * FROM produtos WHERE status = 'ativo' AND (preco < 10 OR preco > 100)
$produtos = (new Produto())
    ->where('status', 'ativo')
    ->where(function($query) {
        $query->where('preco', '<', 10)
              ->orWhere('preco', '>', 100);
    })
    ->get();
```

---

## Paginação

Liste registros com paginação automática — traz os dados **e os metadados** de uma vez:

```php
// Lê automaticamente ?page=X da URL
$resultado = $produtoModel
    ->where('ativo', '1')
    ->orderBy('nome')
    ->paginate(15); // 15 por página

// $resultado contém:
// ['data' => [...], 'total' => 120, 'per_page' => 15,
//  'current_page' => 1, 'last_page' => 8, 'from' => 1, 'to' => 15]

// Na View:
foreach ($resultado['data'] as $produto) {
    echo $produto->nome;
}
echo "Página {$resultado['current_page']} de {$resultado['last_page']}";
```

---

## Aggregações: GroupBy e Having

Para relatórios e dashboards:

```php
// Total de produtos por categoria
$relatorio = $produtoModel
    ->select('categoria_id, COUNT(*) as total')
    ->groupBy('categoria_id')
    ->having('total > 5')
    ->get();

// Valor médio por categoria
$medias = $produtoModel
    ->select('categoria_id, AVG(preco) as preco_medio')
    ->groupBy('categoria_id')
    ->orderBy('preco_medio', 'DESC')
    ->get();
```

---

## Lógica de Join

Use Inner ou Left Joins sem escrever SQL crua:

```php
$produtosComCategoria = $produtoModel->select('produtos.*, categorias.titulo')
    ->join('categorias', 'categorias.id = produtos.categoria_id', 'INNER')
    ->get();
```

---

## Transações de Banco de Dados

Para operações que envolvem **múltiplas tabelas**, use transações para garantir consistência. Se qualquer etapa falhar, tudo é desfeito automaticamente:

```php
// Via Model
$pedidoModel = new Pedido();
$pedidoModel->transaction(function() use ($pedidoData, $itens) {
    // 1. Cria o pedido
    $pedidoId = (new Pedido())->insert($pedidoData);

    // 2. Insere cada item
    foreach ($itens as $item) {
        (new ItemPedido())->insert($item + ['pedido_id' => $pedidoId]);
    }

    // 3. Debita estoque
    (new Estoque())->update($item['produto_id'], [
        'quantidade' => $item['quantidade_nova']
    ]);

    return $pedidoId; // Valor retornado pelo transaction()
});

// Ou diretamente via Connection:
use Core\Database\Connection;

$pedidoId = Connection::transaction(function(\PDO $db) use ($pedidoData) {
    // Usa PDO diretamente se necessário
});
```

---

## Relacionamentos de Model

Defina relacionamentos diretamente na Model para navegação intuitiva.

**Model Produto (belongsTo — Pertence a uma Categoria):**
```php
class Produto extends Model {
    public function categoria(): ?Categoria {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }
}
```

**Model Categoria (hasMany — Tem Vários Produtos):**
```php
class Categoria extends Model {
    public function produtos(): array {
        return $this->hasMany(Produto::class, 'categoria_id');
    }
}
```

**Model Usuario (hasOne — Tem Um Endereço):**
```php
class Usuario extends Model {
    public function endereco(): ?Endereco {
        return $this->hasOne(Endereco::class, 'usuario_id');
    }
}
```

**Uso com Eager Loading (evita N+1):**
```php
// Carrega usuario + endereco numa única query (Eager Loading)
$usuarios = (new Usuario())->with('endereco')->get();

foreach ($usuarios as $usuario) {
    // Aqui não há consultas extras ao banco (resolve o problema N+1)
    echo $usuario->endereco->rua; 
}
```

---

## Soft Deletes (Exclusão Lógica)

O framework permite "deletar" um registro sem removê-lo fisicamente do disco, apenas marcando-o com uma data em `deleted_at`.

Para ativar, adicione a propriedade na sua Model:

```php
class Produto extends Model {
    public bool $softDeletes = true;
}

// O uso do delete() agora apenas preenche a coluna 'deleted_at'
(new Produto())->delete(5);

// Buscas normais IGNORAM os deletados automaticamente
$todos = (new Produto())->all(); 

// Para incluir os deletados na busca
$comDeletados = (new Produto())->withTrashed()->get();

// Para buscar APENAS os que foram deletados
$lixeira = (new Produto())->onlyTrashed()->get();
```

---

## Insert, Update e Delete

```php
// Insert — retorna o ID do novo registro
$id = (new Produto())->insert([
    'nome'  => 'Arroz',
    'preco' => 8.99,
]);

// Update — retorna bool
(new Produto())->update($id, ['preco' => 9.49]);

// Delete (hard delete ou soft delete se $softDeletes = true)
(new Produto())->delete($id);
```

---

## Mass Assignment e Proteção

A propriedade `$fillable` protege contra o envio malicioso de campos como `nivel_admin=1`:

```php
class User extends Model {
    protected array $fillable = ['nome', 'email', 'password'];
    // Qualquer campo fora dessa lista é silenciosamente ignorado no insert/update
}
```

> **Aviso:** Se `$fillable` estiver vazio e `APP_DEBUG=true`, o framework emitirá um alerta no log para lembrá-lo de definí-lo.

---

## Ocultando Campos Sensíveis (Hidden Fields)

Para evitar que campos como `senha` ou `token` sejam expostos ao converter o Model para Array ou JSON (e também no `var_dump()` / `debugInfo`), use a propriedade `$hidden`:

```php
class Usuario extends Model {
    protected array $hidden = ['password', 'token_recuperacao'];
}

// Ao converter para array ou enviar como JSON:
$usuario = (new Usuario())->find(1);
return $usuario->toArray(); // 'password' e 'token_recuperacao' não estarão presentes
```

---

## Query SQL Crua (Escape Hatch)

Para casos onde o Query Builder não alcança, use o método `query()`:

```php
$resultado = (new Produto())->query(
    'SELECT p.*, c.titulo FROM produtos p LEFT JOIN categorias c ON c.id = p.categoria_id WHERE p.preco > :preco',
    ['preco' => 50]
);
```

---

## Contagem de Registros

```php
$totalProdutos = (new Produto())->count();

// Filtrada
$ativos = (new Produto())->where('ativo', '1')->count();
```

---

## Drivers de Banco Suportados

| Driver | Configuração em `.env` |
|---|---|
| MySQL / MariaDB | `DB_CONNECTION=mysql` |
| PostgreSQL | `DB_CONNECTION=pgsql` |
| SQLite (testes/dev) | `DB_CONNECTION=sqlite` |

Para **SQLite em memória** (ideal para testes automatizados):
```env
DB_CONNECTION=sqlite
DB_DATABASE=:memory:
```
