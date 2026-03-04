# Estrutura de Diretórios

O framework segue uma separação lógica e profissional de pastas:

- **`app/`**: Onde você vai passar 90% do seu tempo.
  - **`Controllers/`**: Orquestram as requisições e a lógica de apresentação.
  - **`DTOs/`**: "Gatekeepers" que validam, tipam e autorizam os dados antes de chegarem aos controladores.
  - **`Services/`**: Regras de negócio pesadas (Cálculos de frete, Integrações de Pagamento e processamentos complexos).
  - **`Models/`**: Representam as tabelas do Banco, comportam o Query Builder e relacionamentos.
  - **`Middleware/`**: "Filtros" (Ex: Bloquear usuários deslogados).
  - **`Views/`**: O visual do seu site (HTML/PHP ou Twig).
  - **`Mutators/`** e **`Rules/`**: Suas Inteligências Mágicas criadas para manipular e validar campos.
  - **`Providers/`**: Seus registradores de serviços de inicialização.
- **`bootstrap/`**: Responsável pelo script de inicialização do cache mágico e motor do framework.
- **`config/`**: Configurações de super variáveis (`app.php`, `database.php` e `middleware.php` para aliases dos seus filtros).
- **`core/`**: O motor do framework (Não mexa aqui dentro a não ser que vá contribuir com a arquitetura núcleo da engine).
- **`database/`**: Configurações de Banco e **`migrations/`** de tabelas.
- **`public/`**: A única pasta com acesso via Web (Contém o seu Arquivo `index.php` e os seus CSS/JS/Imagens).
- **`routes/`**: Define as URLs e Grupos de URLs disponíveis no seu App (`web.php`).
- **`storage/logs/`**: Logs de erros escondidos (`app.log`) ocorridos em Produção.
