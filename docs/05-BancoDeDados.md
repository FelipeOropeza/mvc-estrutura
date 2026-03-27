# Banco de Dados & ORM

O framework possui um ORM próprio, composto por três camadas:

| Camada | Arquivo | Responsabilidade |
|---|---|---|
| `Connection` | `core/Database/Connection.php` | Singleton PDO com reconexão automática |
| `Model` | `core/Database/Model.php` | Active Record — interface de alto nível para a tabela |
| `QueryBuilder` | `core/Database/QueryBuilder.php` | Fluent query builder — encadeia cláusulas e executa |

> O `Model` sempre delega para o `QueryBuilder`. O `QueryBuilder` é o único que fala com o PDO.

---

## Configuração da Connection

A conexão é um **Singleton** — criado uma única vez e reutilizado durante toda a requisição (ou ciclo de worker no FrankenPHP). Ele executa `SELECT 1` a cada uso para detectar conexões mortas e reconectar automaticamente.

Configure via `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=meu_banco
DB_USERNAME=root
DB_PASSWORD=secret
```

Drivers suportados:

| Driver | `DB_CONNECTION` |
|---|---|
| MySQL / MariaDB | `mysql` |
| PostgreSQL | `pgsql` |
| SQLite | `sqlite` |

Para SQLite em memória (ideal para testes):

```env
DB_CONNECTION=sqlite
DB_DATABASE=:memory:
```

---

## Criando uma Model

Toda Model fica em `app/Models` e estende `Core\Database\Model`:

```php
namespace App\Models;

use Core\Database\Model;

class Produto extends Model
{
    // (Opcional) Nome da tabela. Se omitido, usa o plural do nome da classe em lowercase.
    // Ex: Produto -> 'produtos', UserAddress -> 'user_addresses'
    protected ?string $table = 'produtos';

    // Chave primária (padrão: 'id')
    protected string $primaryKey = 'id';

    // Campos que podem ser escritos via insert() e update() (Mass Assignment Guard)
    protected array $fillable = ['nome', 'preco', 'ativo', 'categoria_id'];

    // Campos ocultados no toArray(), JSON e var_dump()
    protected array $hidden = ['custo_interno'];

    // Gerencia created_at e updated_at automaticamente (padrão: true)
    public bool $timestamps = true;

    // Ativa exclusão lógica via deleted_at (padrão: false)
    public bool $softDeletes = false;
}
```

### Propriedades da Model

| Propriedade | Tipo | Padrão | Descrição |
|---|---|---|---|
| `$table` | `?string` | `null` (auto) | Nome da tabela no banco |
| `$primaryKey` | `string` | `'id'` | Coluna da chave primária |
| `$fillable` | `array` | `[]` | Colunas permitidas para escrita em massa |
| `$hidden` | `array` | `[]` | Colunas omitidas na serialização |
| `$timestamps` | `bool` | `true` | Preenche `created_at`/`updated_at` automaticamente |
| `$softDeletes` | `bool` | `false` | Usa `deleted_at` em vez de `DELETE` |

---

## Buscas Básicas

```php
$model = new Produto();

// Todos os registros
$todos = $model->all(); // array de instâncias Produto

// Por ID — retorna null se não existir
$produto = $model->find(5);

// Por ID — lança HttpException 404 se não existir (use em Controllers)
$produto = $model->findOrFail(5);

// Primeiro registro da tabela
$primeiro = $model->first();
```

---

## Query Builder Fluente

Use encadeamento de métodos para construir queries sem escrever SQL à mão. Todo método de filtragem em um `Model` **sempre cria um novo `QueryBuilder`**, garantindo que instâncias diferentes não se contaminem.

```php
$produtos = (new Produto())
    ->select('nome, preco, categoria_id')   // Colunas (padrão: *)
    ->where('ativo', '1')                   // Forma curta (operador = por padrão)
    ->where('preco', '>', 50)               // Forma completa com operador
    ->orderBy('preco', 'DESC')
    ->limit(10)
    ->offset(0)
    ->get();                                // Executa e retorna array
```

### Tabela de Métodos do QueryBuilder

| Método | Descrição |
|---|---|
| `select(string $cols)` | Define as colunas a retornar |
| `where($col, $op?, $val)` | Adiciona condição AND (aceita Closure para agrupamento) |
| `orWhere($col, $op?, $val)` | Adiciona condição OR (aceita Closure para agrupamento) |
| `whereIn(string $col, array\|$Closure $vals)` | `col IN (...)` — AND (aceita array ou Closure para Subquery) |
| `orWhereIn(string $col, array $vals)` | `col IN (...)` — OR |
| `whereNull(string $col)` | `col IS NULL` |
| `whereNotNull(string $col)` | `col IS NOT NULL` |
| `join($table, $cond, $type)` | JOIN (padrão INNER) |
| `leftJoin($table, $cond)` | LEFT JOIN |
| `groupBy(string $col)` | GROUP BY |
| `having(string $cond)` | HAVING |
| `orderBy(string $col, $dir)` | ORDER BY (acumulativo se chamado múltiplas vezes) |
| `limit(int $n)` | LIMIT |
| `offset(int $n)` | OFFSET |
| `with($rels)` | Eager Loading (aceita array associativo com Closure e dot notation) |
| `withTrashed()` | Inclui soft-deletados |
| `onlyTrashed()` | Somente soft-deletados |
| `get()` | Executa e retorna `array` |
| `first()` | Executa com LIMIT 1, retorna objeto ou `null` |
| `find(mixed $id)` | Busca o registro pelo ID (`where('id', '=', $id)->first()`) |
| `count(string $col)` | Retorna `COUNT(col)` como `int` |
| `paginate(int $perPage, ?int $page)` | Retorna dados + metadados de paginação |
| `delete()` | Executa DELETE com os WHEREs aplicados |

### Omissão do Operador

Quando o segundo argumento é o valor (sem operador), o `=` é assumido automaticamente:

```php
// Estas duas formas são equivalentes:
->where('ativo', '=', '1')
->where('ativo', '1')
```

---

## Condições Avançadas com Closures

Para criar grupos de condições `(A AND B) OR (C AND D)`, passe uma `Closure` para `where()` ou `orWhere()`:

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

A Closure recebe um `QueryBuilder` isolado. Os parâmetros são mergeados de forma segura, sem colisão de nomes.

---

## Subqueries com whereIn

O método `whereIn` aceita uma `Closure` que permite construir uma subquery SQL de forma fluente:

```php
// SELECT * FROM produtos WHERE categoria_id IN (SELECT id FROM categorias WHERE ativo = 1)
$produtos = (new Produto())
    ->whereIn('categoria_id', function($query) {
        $query->select('id')
              ->from('categorias')
              ->where('ativo', '1');
    })
    ->get();
```

> **Nota:** A subquery é instanciada isoladamente. Se a tabela da subquery usar Soft Deletes, os filtros (como `deleted_at IS NULL`) devem ser aplicados manualmente dentro da Closure se desejado, pois ela inicia um contexto limpo.

---

## Joins

```php
// INNER JOIN (padrão)
$produtos = (new Produto())
    ->select('produtos.*, categorias.titulo as categoria')
    ->join('categorias', 'categorias.id = produtos.categoria_id')
    ->get();

// LEFT JOIN
$produtos = (new Produto())
    ->select('produtos.*, avaliacoes.nota')
    ->leftJoin('avaliacoes', 'avaliacoes.produto_id = produtos.id')
    ->get();

// Múltiplos JOINs encadeados
$resultado = (new Pedido())
    ->select('pedidos.id, clientes.nome, produtos.nome as produto')
    ->join('clientes', 'clientes.id = pedidos.cliente_id')
    ->join('itens_pedido', 'itens_pedido.pedido_id = pedidos.id')
    ->join('produtos', 'produtos.id = itens_pedido.produto_id')
    ->get();
```

---

## Paginação

O `paginate()` lê `?page=X` da URL automaticamente e retorna dados + metadados em uma única chamada:

```php
$resultado = (new Produto())
    ->where('ativo', '1')
    ->orderBy('nome')
    ->paginate(15); // 15 por página

// Estrutura retornada:
// [
//   'data'         => [...],   // Array de Produto
//   'total'        => 120,     // Total de registros
//   'per_page'     => 15,
//   'current_page' => 1,
//   'last_page'    => 8,
//   'from'         => 1,       // Primeiro registro desta página
//   'to'           => 15,      // Último registro desta página
// ]

foreach ($resultado['data'] as $produto) {
    echo $produto->nome;
}
echo "Página {$resultado['current_page']} de {$resultado['last_page']}";
```

> **Atenção:** Usar `paginate()` com `groupBy()` pode retornar número de grupos em vez de registros totais. Nesses casos, prefira construir a contagem manualmente.

---

## Aggregações: GroupBy e Having

```php
// Contagem por categoria
$relatorio = (new Produto())
    ->select('categoria_id, COUNT(*) as total')
    ->groupBy('categoria_id')
    ->having('total > 5')
    ->orderBy('total', 'DESC')
    ->get();

// Valor médio por categoria
$medias = (new Produto())
    ->select('categoria_id, AVG(preco) as preco_medio')
    ->groupBy('categoria_id')
    ->get();
```

---

## Insert, Update e Delete

```php
$model = new Produto();

// Insert — retorna o int do ID gerado
$id = $model->insert([
    'nome'        => 'Arroz',
    'preco'       => 8.99,
    'ativo'       => true, // Booleanos são convertidos automaticamente para 1/0 no SQL
    'categoria_id' => 2,
]);

// Update — retorna bool
$model->update($id, ['preco' => 9.49]);

// Hard delete por ID (ou soft delete se $softDeletes = true)
$model->delete($id);
```

> **timestamps automáticos:** Se `$timestamps = true`, o `insert()` preenche `created_at` e `updated_at`, e o `update()` atualiza apenas `updated_at`.

### DELETE condicional via QueryBuilder

Para deletar múltiplos registros com filtros:

```php
// Deleta todos os produtos inativos com estoque zero
(new Produto())
    ->where('ativo', '0')
    ->where('estoque', '0')
    ->delete();
```

> **Segurança:** Chamar `delete()` **sem nenhum `where()`** lança uma `\LogicException`. Não é possível apagar uma tabela inteira acidentalmente.

---

## Mass Assignment & Proteção

A propriedade `$fillable` define quais colunas são aceitas pelo `insert()` e `update()`. Campos fora da lista são **silenciosamente ignorados**:

```php
class User extends Model
{
    protected array $fillable = ['nome', 'email', 'password'];
    // 'nivel_admin', 'saldo' etc. nunca serão escritos via insert/update
}

// Mesmo que alguém envie 'nivel_admin' no POST, ele é descartado:
(new User())->insert(request()->all());
```

> Se `$fillable` estiver **vazio** e `APP_DEBUG=true`, o framework emite um `E_USER_NOTICE` no log para alertar o desenvolvedor. Em produção, simplesmente nada é escrito.

---

## Campos Ocultos (hidden)

A propriedade `$hidden` oculta colunas sensíveis em `toArray()`, `jsonSerialize()` e `var_dump()`:

```php
class Usuario extends Model
{
    protected array $hidden = ['password', 'token_recuperacao'];
}

$usuario = (new Usuario())->find(1);

// 'password' e 'token_recuperacao' não aparecem:
$usuario->toArray();
json_encode($usuario);
var_dump($usuario); // Também oculta via __debugInfo()
```

---

## Soft Deletes (Exclusão Lógica)

Ao ativar `$softDeletes = true`, o `delete()` **não remove** o registro — apenas preenche a coluna `deleted_at`. Todas as buscas normais excluem automaticamente registros com `deleted_at IS NOT NULL`.

**Requisito:** A tabela deve ter a coluna `deleted_at DATETIME NULL`.

```php
class Produto extends Model
{
    public bool $softDeletes = true;
}

$model = new Produto();

// "Deleta" (preenche deleted_at)
$model->delete(5);

// Buscas normais ignoram os deletados automaticamente
$ativos = $model->all();

// Inclui deletados + ativos
$todos = $model->withTrashed()->get();

// Somente os deletados (lixeira)
$lixeira = $model->onlyTrashed()->get();

// Restaurar: basta update() zerando o campo
$model->update(5, ['deleted_at' => null]);
```

---

## Relacionamentos

Defina relacionamentos como métodos na Model. O ORM suporta **Lazy Loading** (executa ao acessar a propriedade) e **Eager Loading** (carrega junto com `with()`, evitando N+1).

### `belongsTo` — Pertence A

Um `Produto` pertence a uma `Categoria`:

```php
class Produto extends Model
{
    public function categoria(): ?Categoria
    {
        // belongsTo(Classe, chave_estrangeira_nesta_tabela, chave_primária_da_outra)
        return $this->belongsTo(Categoria::class, 'categoria_id', 'id');
    }
}

// Lazy Loading — consulta ao banco ao acessar:
$produto = (new Produto())->find(1);
echo $produto->categoria->titulo;
```

### `hasMany` — Tem Vários

Uma `Categoria` tem vários `Produto`s:

```php
class Categoria extends Model
{
    public function produtos(): array
    {
        // hasMany(Classe, chave_estrangeira_na_outra_tabela, chave_local)
        return $this->hasMany(Produto::class, 'categoria_id', 'id');
    }
}

$categoria = (new Categoria())->find(2);
foreach ($categoria->produtos as $produto) {
    echo $produto->nome;
}
```

### `hasOne` — Tem Um

Um `Usuario` tem um `Endereco`:

```php
class Usuario extends Model
{
    public function endereco(): ?Endereco
    {
        return $this->hasOne(Endereco::class, 'usuario_id', 'id');
    }
}

$usuario = (new Usuario())->find(1);
echo $usuario->endereco->rua;
```

### Eager Loading — Evitando o Problema N+1

Sem Eager Loading, acessar uma relação num loop gera **uma query por item** (N+1):

```php
// ❌ Ruim — gera 1 + N queries:
$produtos = (new Produto())->all();
foreach ($produtos as $produto) {
    echo $produto->categoria->titulo; // nova query a cada iteração
}
```

Com `with()`, o ORM carrega **tudo em 2 queries** (uma para a tabela principal, uma para a relação):

```php
// ✅ Bom — 2 queries no total, independente do volume:
$produtos = (new Produto())->with('categoria')->get();
foreach ($produtos as $produto) {
    echo $produto->categoria->titulo; // sem query extra
}

// Múltiplas relações de uma vez:
$usuarios = (new Usuario())->with('endereco', 'pedidos')->get();

// Ou como array:
$usuarios = (new Usuario())->with(['endereco', 'pedidos'])->get();
```

> O Eager Loading funciona para `belongsTo`, `hasMany` e `hasOne`. Relações não encontradas retornam `null` (hasOne/belongsTo) ou `[]` (hasMany).

### Eager Loading com Filtros (Constrained Eager Loading)

Você pode filtrar ou ordenar os registros relacionados passando um array associativo para o `with()`:

```php
// Carrega os usuários e, para cada um, apenas os pedidos 'pagos', ordenados pelo ID
$usuarios = (new Usuario())->with([
    'pedidos' => function($query) {
        $query->where('status', 'pago')
              ->orderBy('id', 'DESC');
    }
])->get();

// Você pode misturar relações simples e relações filtradas
$usuarios = (new Usuario())->with([
    'endereco', // Carregamento simples
    'pedidos' => fn($q) => $q->where('total', '>', 100) // Filtrado
])->get();
```

### Eager Loading Aninhado (Nested Relations)

É possível carregar relações de relações de forma otimizada utilizando a notação de ponto (dot notation):

```php
$usuarios = (new Usuario())->with('pedidos.itens.produto')->get();
// Carregará os usuários
// + os pedidos
// + os itens de cada pedido
// + o produto de cada item, blindando N+1 em todos os níveis.
```

---

## Transações

Use transações para garantir consistência em operações que envolvem múltiplas tabelas. Em caso de qualquer exceção, o `rollback` é automático:

```php
use Core\Database\Connection;

// Via method do Model
(new Pedido())->transaction(function() use ($pedidoData, $itens) {
    $pedidoId = (new Pedido())->insert($pedidoData);

    foreach ($itens as $item) {
        (new ItemPedido())->insert($item + ['pedido_id' => $pedidoId]);
    }

    (new Estoque())->update($item['produto_id'], [
        'quantidade' => $item['quantidade_nova']
    ]);

    return $pedidoId; // Valor retornado pela transaction()
});

// Ou diretamente pela Connection (quando precisar do PDO raw):
$pedidoId = Connection::transaction(function(\PDO $db) use ($pedidoData) {
    $stmt = $db->prepare('INSERT INTO ...');
    // ...
});
```

> Transações são **aninhadas com segurança**: se o callback lançar qualquer `Throwable`, o rollback acontece e a exceção é relançada normalmente.

---

## Serialização: toArray() e JSON

Toda Model implementa `\JsonSerializable`, então você pode usar `json_encode()` diretamente. O método `toArray()` foi projetado para extrair mapeamentos limpos até mesmo de propriedades dinâmicas ou privadas/protegidas, excluindo o estado acoplado pelo framework. Os campos definidos na propriedade `$hidden` são totalmente blindados:

```php
$produto = (new Produto())->find(1);

// Para array PHP:
$arr = $produto->toArray();

// Para JSON (automaticamente usa toArray() internamente):
echo json_encode($produto);

// Retornar JSON numa Response:
return response()->json($produto);

// Relações carregadas também são serializadas:
$produto = (new Produto())->with('categoria')->first();
json_encode($produto); // inclui o objeto 'categoria' aninhado
```

---

## Query SQL Crua (Escape Hatch)

Para casos onde o Query Builder não alcança:

```php
$resultado = (new Produto())->query(
    'SELECT p.*, c.titulo FROM produtos p
     LEFT JOIN categorias c ON c.id = p.categoria_id
     WHERE p.preco > :preco AND c.ativo = :ativo',
    ['preco' => 50, 'ativo' => 1]
);
// Retorna array associativo (FETCH_ASSOC), não objetos Model
```

---

## Contagem de Registros

```php
// Total da tabela
$total = (new Produto())->count();

// Com filtros
$ativos = (new Produto())->where('ativo', '1')->count();

// Coluna específica (COUNT(preco) — ignora NULLs)
$comPreco = (new Produto())->count('preco');
```

---

## Schema Builder (Migrations)

O Schema Builder é usado nas [Migrations](12-CLIMigrations.md) para criar e modificar tabelas:

```php
use Core\Database\Schema\Schema;

Schema::create('produtos', function(\Core\Database\Schema\Blueprint $table) {
    $table->id();                              // INT UNSIGNED AUTO_INCREMENT PRIMARY KEY
    $table->string('nome', 200);               // VARCHAR(200) NOT NULL
    $table->string('slug')->unique();          // VARCHAR(255) UNIQUE
    $table->text('descricao');                 // TEXT
    $table->decimal('preco', 10, 2);           // DECIMAL(10,2)
    $table->integer('estoque')->default(0);    // INT DEFAULT 0
    $table->boolean('ativo')->default(true);   // TINYINT(1)
    $table->date('validade')->nullable();       // DATE NULL
    $table->datetime('publicado_em')->nullable(); // DATETIME NULL
    $table->enum('status', ['rascunho', 'publicado', 'arquivado']); // ENUM
    $table->unsignedInteger('categoria_id');

    $table->foreign('categoria_id')
          ->references('id')
          ->on('categorias')
          ->onDelete('CASCADE');

    $table->timestamps();  // created_at e updated_at DATETIME NULL
    $table->softDeletes(); // deleted_at DATETIME NULL
});
```

### Tipos de Coluna Disponíveis

| Método | SQL gerado |
|---|---|
| `id(string $name = 'id')` | `INT UNSIGNED AUTO_INCREMENT` + PRIMARY KEY |
| `string(string $name, int $len = 255)` | `VARCHAR(n)` |
| `text(string $name)` | `TEXT` |
| `integer(string $name)` | `INT` |
| `decimal(string $name, $p, $s)` | `DECIMAL(p,s)` |
| `boolean(string $name)` | `TINYINT(1)` |
| `date(string $name)` | `DATE` |
| `datetime(string $name)` | `DATETIME` |
| `timestamp(string $name)` | `TIMESTAMP` |
| `enum(string $name, array $values)` | `ENUM('v1','v2',...)` |
| `timestamps()` | Atalho: `created_at` + `updated_at` como `DATETIME NULL` |
| `softDeletes()` | Atalho: `deleted_at` como `DATETIME NULL` |

### Modificadores de Coluna (Fluent)

```php
$table->string('apelido')->nullable();          // NULL permitido
$table->integer('views')->default(0);           // Valor padrão
$table->boolean('ativo')->default(true);        // Boolean auto-traduz para DEFAULT 1 nativamente
$table->string('codigo')->unique();             // UNIQUE KEY
$table->integer('quantidade')->unsigned();      // UNSIGNED
$table->id('produto_id')->autoIncrement();      // AUTO_INCREMENT (incluído em id())
```
