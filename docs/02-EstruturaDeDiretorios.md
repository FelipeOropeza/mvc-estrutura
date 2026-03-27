# Estrutura de Diretórios

O framework adota o conceito de "Progressive Disclosure". Pastas de arquitetura avançadas (como `DTOs`, `Rules`, `Jobs`) só são criadas quando você solicita via linha de comando (`php forge`).

- **`app/`**: Onde fica a lógica do seu sistema (Namespace `App\`).
  - **`Controllers/`**: Orquestram as requisições e a lógica de apresentação.
  - **`Models/`**: Representam as tabelas do Banco, comportam o Query Builder e relacionamentos.
  - **`Middleware/`**: "Filtros" (Ex: Bloquear usuários deslogados).
  - *Pastas como `DTOs/`, `Mutators/`, `Providers/`, `Jobs/`, `Services/` serão criadas dinamicamente à medida que você for implementando funcionalidades avançadas via `php forge`.*
- **`resources/`**: Arquivos que não são classes de negócio.
  - **`views/`**: O visual do seu site em HTML ou PHP nativo.
- **`bootstrap/`**: Scripts de inicialização do motor do framework.
- **`config/`**: Configurações de super variáveis (`app.php`, `database.php` etc).
- **`core/`**: O motor do framework (Não mexa aqui dentro, é seu código de base).
- **`database/`**: Configurações de Banco, **`migrations/`** e **`seeders/`**.
- **`public/`**: A única pasta com acesso via Web (Contém o seu Arquivo `index.php` e os seus CSS/JS/Imagens).
- **`routes/`**: Define URL Paths. *Obs: Recomendamos fortemente definir as rotas diretamente nos seus Controllers via Atributos PHP 8 (`#[Get('/rota')]`), deixando a pasta `routes/` mais enxuta!*
- **`storage/`**: Arquivos temporários, logs (`logs/app.log`) e uploads (se usar disco local).
