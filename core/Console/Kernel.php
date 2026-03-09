<?php

declare(strict_types=1);

namespace Core\Console;

class Kernel
{
    private array $config;

    public function __construct()
    {
        // Carrega o arquivo de configuração
        $this->config = require __DIR__ . '/../../config/app.php';
    }

    public function handle(array $args): void
    {
        array_shift($args); // Remove o nome do script

        if (empty($args)) {
            $this->showHelp();
            exit(1);
        }

        $command = $args[0];

        switch ($command) {
            case 'make:migration':
                $this->makeMigration($args);
                break;
            case 'migrate':
                $this->runMigrations($args);
                break;
            case 'make:controller':
                $this->makeController($args);
                break;
            case 'make:model':
                $this->makeModel($args);
                break;
            case 'make:service':
                $this->makeService($args);
                break;
            case 'make:view':
                $this->makeView($args);
                break;
            case 'make:middleware':
                $this->makeMiddleware($args);
                break;
            case 'make:rule':
                $this->makeRule($args);
                break;
            case 'make:mutator':
                $this->makeMutator($args);
                break;
            case 'setup:engine':
                $this->setupEngine($args);
                break;
            case 'setup:auth':
                $this->setupAuth($args);
                break;
            case 'setup:api':
                $this->setupApi($args);
                break;
            case 'optimize':
                $this->optimizeApp($args);
                break;
            case 'optimize:clear':
                $this->clearOptimization($args);
                break;
            case 'migrate:refresh':
                $this->migrateRefresh($args);
                break;
            case 'make:dto':
                $this->makeDto($args);
                break;
            case 'make:seeder':
                $this->makeSeeder($args);
                break;
            case 'make:component':
                $this->makeComponent($args);
                break;
            case 'serve':
                $this->serveApp($args);
                break;
            case 'queue:work':
                $this->queueWork($args);
                break;
            default:
                echo "Erro: Comando não reconhecido: '$command'\n";
                $this->showHelp();
                exit(1);
        }
    }

    private function showHelp(): void
    {
        echo "MVC Base Console\n";
        echo "=================\n";
        echo "Uso: forge [comando] ou php forge [comando]\n\n";
        echo "Comandos disponíveis:\n";
        echo "  make:controller <Nome>   Cria um novo Controller\n";
        echo "  make:model <Nome>        Cria um novo Model\n";
        echo "  make:view <Nome>         Cria uma nova View automaticamente na extensão correta\n";
        echo "  make:component <Nome>    Cria um novo Componente HTMX reativo. Ex: table_users\n";
        echo "  make:service <Nome>      Cria um novo Service de regra de negócio para injetar. Ex: UserService\n";
        echo "  make:migration <Nome>    Cria uma nova Migration de Banco de Dados. Ex: CreateUsersTable\n";
        echo "  make:middleware <Nome>   Cria um novo Middleware de validação. Ex: AuthMiddleware\n";
        echo "  make:rule <Nome>         Cria um atributo de Validação customizado. Ex: CpfValido\n";
        echo "  make:mutator <Nome>      Cria um atributo de Mutação customizado. Ex: LimpaCpf\n";
        echo "  make:dto <Nome>          Cria um Data Transfer Object. Ex: Admin/LoginDTO\n";
        echo "  make:seeder <Nome>       Cria uma nova classe de Seeder. Ex: DatabaseSeeder\n";
        echo "  migrate                  Gera o Banco de Dados ausente (se possível) e roda as Migrations\n";
        echo "  db:seed [Nome]           Executa o seeder especificado ou a classe DatabaseSeeder\n";
        echo "  migrate:refresh          Desfaz todas as migrations e re-executa do zero\n";
        echo "  setup:engine <php|twig>  Muda o motor padrão do projeto e limpa views não utilizadas\n";
        echo "  setup:auth               Instala o scaffolding completo de Autenticação (Login, Registro, DB)\n";
        echo "  setup:api                Instala o scaffolding de API (JWT, AuthController, DTOs, Rotas)\n";
        echo "  optimize                 Compila as rotas e dependências para máxima performance\n";
        echo "  optimize:clear           Remove os arquivos de cache compilados\n";
        echo "  serve                    Inicia o servidor de desenvolvimento local\n";
        echo "  queue:work [nome]        Inicia um worker para processar a fila especificada\n";
    }

    private function makeMigration(array $args): void
    {
        if (!isset($args[1])) {
            echo "Erro: Forneça o nome da classe. Ex: make:migration CreateUsersTable\n";
            exit(1);
        }

        $name = $args[1];
        $dir = $this->config['paths']['migrations'] ?? __DIR__ . '/../../database/migrations';

        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        // Gera o prefixo UNIX pra manter a ordem (Estilo Laravel: 2023_01_01_102030_CreateUsersTable.php)
        $fileName = date('Y_m_d_His') . '_' . $name . '.php';
        $path = $dir . '/' . $fileName;

        // Tenta descobrir o nome da tabela (ex: "users" vindo de "CreateUsersTable")
        $tableName = 'tabela_nova';
        if (preg_match('/Create(.*)Table/i', $name, $matches)) {
            $tableName = strtolower($matches[1]);
        }

        $content = $this->renderTemplate('migration', [
            '{{name}}' => $name,
            '{{tableName}}' => $tableName
        ]);

        $this->createFile($path, $content, "Migration '$name'");
    }

    private function runMigrations(array $args): void
    {
        echo "Iniciando as Migrations...\n========================\n";

        $dbConfigPath = __DIR__ . '/../../config/database.php';
        if (!file_exists($dbConfigPath)) {
            echo "Erro: Arquivo config/database.php não encontrado.\n";
            exit(1);
        }
        $dbConfigMaster = require $dbConfigPath;

        $driver = getenv('DB_CONNECTION') ?: $dbConfigMaster['default'];
        $dbConfig = $dbConfigMaster['connections'][$driver];

        // 1. Opcionalmente: Cria o Banco se não existir (MySQL puro suporta criar)
        try {
            if ($driver === 'mysql') {
                $dsnOutDB = "mysql:host={$dbConfig['host']};port={$dbConfig['port']}";
                $pdoCheck = new \PDO($dsnOutDB, $dbConfig['username'], $dbConfig['password']);
                $pdoCheck->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

                $dbName = $dbConfig['database'];
                // Verifica e cria
                $pdoCheck->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET {$dbConfig['charset']} COLLATE utf8mb4_unicode_ci;");
            }
        } catch (\PDOException $e) {
            echo "Erro Crítico de Conexão: O servidor não atendeu com essas credenciais.\n";
            echo "Detalhe: " . $e->getMessage() . "\n";
            exit(1);
        }

        // Conexão com o banco de dados da aplicação para gerenciar a tabela de migrations
        $pdoApp = null;
        try {
            if ($driver === 'mysql') {
                $dsnApp = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['database']}";
                $pdoApp = new \PDO($dsnApp, $dbConfig['username'], $dbConfig['password']);
                $pdoApp->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

                // Criar a tabela migrations se não existir
                $pdoApp->exec("CREATE TABLE IF NOT EXISTS `migrations` (
                    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `migration` VARCHAR(255) NOT NULL,
                    `batch` INT NOT NULL
                ) ENGINE=InnoDB DEFAULT CHARSET={$dbConfig['charset']} COLLATE=utf8mb4_unicode_ci;");
            }
        } catch (\PDOException $e) {
            echo "Erro Crítico ao conectar no banco de dados da aplicação.\n";
            echo "Detalhe: " . $e->getMessage() . "\n";
            exit(1);
        }

        // Buscar as migrations já rodadas
        $ranMigrations = [];
        $nextBatch = 1;
        if ($pdoApp) {
            $stmt = $pdoApp->query("SELECT migration FROM migrations");
            $ranMigrations = $stmt->fetchAll(\PDO::FETCH_COLUMN);

            $stmtBatch = $pdoApp->query("SELECT MAX(batch) FROM migrations");
            $nextBatch = ((int) $stmtBatch->fetchColumn()) + 1;
        }

        $dir = $this->config['paths']['migrations'] ?? __DIR__ . '/../../database/migrations';

        if (!is_dir($dir)) {
            echo "Tudo certo! Mas você ainda não possui a pasta de Migrations.\n";
            exit(1);
        }

        $files = scandir($dir);
        // Garante a ordenação alfabética (que equivale a cronológica devido ao formato Y_m_d_His)
        sort($files);
        $ranAny = false;

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            // Se a migration já foi rodada, pular
            if (in_array($file, $ranMigrations)) {
                continue;
            }

            $path = $dir . '/' . $file;

            // Pega o nome da classe removendo timestamp e extensão
            // Ex: "2023_01_01_102030_CreateUsersTable.php" vira "CreateUsersTable"
            $className = preg_replace('/^[0-9_]+_([a-zA-Z0-9]+)\.php$/', '$1', $file);

            if ($className && is_file($path)) {
                require_once $path;

                // Suportar classes com namespace
                $namespacedClass = "\\App\\Database\\Migrations\\$className";
                if (class_exists($namespacedClass)) {
                    $className = $namespacedClass;
                }

                if (class_exists($className)) {
                    $migration = new $className();

                    if (method_exists($migration, 'up')) {
                        echo "\n[INFO] Rodando: $file\n";
                        $migration->up();

                        // Registra no banco
                        if ($pdoApp) {
                            $stmtInsert = $pdoApp->prepare("INSERT INTO migrations (migration, batch) VALUES (?, ?)");
                            $stmtInsert->execute([$file, $nextBatch]);
                        }

                        $ranAny = true;
                    }
                }
            }
        }

        if (!$ranAny) {
            echo "Nenhuma Migration pendente encontrada para rodar.\n";
        } else {
            echo "\n✅ Todas as migrations concluídas com sucesso.\n";
        }
    }

    private function makeController(array $args): void
    {
        if (!isset($args[1])) {
            echo "Erro: Forneça o nome. Ex: make:controller UsuarioController\n";
            exit(1);
        }

        $name = $args[1];
        if (!str_ends_with($name, 'Controller')) {
            $name .= 'Controller';
        }

        $path = $this->config['paths']['controllers'] . '/' . $name . '.php';
        $content = $this->renderTemplate('controller', ['{{name}}' => $name]);
        $this->createFile($path, $content, "Controller '$name'");
    }

    private function makeModel(array $args): void
    {
        if (!isset($args[1])) {
            echo "Erro: Forneça o nome. Ex: make:model Usuario\n";
            exit(1);
        }

        $name = $args[1];
        $dir = $this->config['paths']['models'];
        $path = $dir . '/' . $name . '.php';

        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $content = $this->renderTemplate('model', ['{{name}}' => $name]);
        $this->createFile($path, $content, "Model '$name'");
    }

    private function makeService(array $args): void
    {
        if (!isset($args[1])) {
            echo "Erro: Forneça o nome. Ex: make:service UsuarioService\n";
            exit(1);
        }

        $name = $args[1];
        if (!str_ends_with($name, 'Service')) {
            $name .= 'Service';
        }

        $dir = $this->config['paths']['services'] ?? __DIR__ . '/../../app/Services';
        $path = $dir . '/' . $name . '.php';

        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $content = $this->renderTemplate('service', ['{{name}}' => $name]);
        $this->createFile($path, $content, "Service '$name'");
    }

    private function makeView(array $args): void
    {
        if (!isset($args[1])) {
            echo "Erro: Forneça o nome. Ex: make:view usuario/perfil\n";
            exit(1);
        }

        $name = $args[1];

        // Pega do seu config/app.php o motor base atual ('php' ou 'twig')
        $engine = $this->config['app']['view_engine'] ?? 'php';
        $extension = $engine === 'twig' ? '.twig' : '.php';

        // Anexa a extensão baseada no motor dinamicamente ao nome se não tem
        if (!str_ends_with($name, $extension) && !str_ends_with($name, '.html')) {
            // Remove se a pessoa digitou a outra sem querer
            $name = str_replace(['.php', '.twig'], '', $name);
            $name .= $extension;
        }

        $path = $this->config['paths']['views'] . '/' . $name;
        $dir = dirname($path);

        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $content = $this->renderTemplate('view', ['{{name}}' => $name]);
        $this->createFile($path, $content, "View '$name'");
    }

    private function makeMiddleware(array $args): void
    {
        if (!isset($args[1])) {
            echo "Erro: Forneça o nome. Ex: make:middleware AuthMiddleware\n";
            exit(1);
        }

        $name = $args[1];
        if (!str_ends_with($name, 'Middleware')) {
            $name .= 'Middleware';
        }

        $dir = $this->config['paths']['middlewares'] ?? __DIR__ . '/../../app/Middleware';
        $path = $dir . '/' . $name . '.php';

        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $content = $this->renderTemplate('middleware', ['{{name}}' => $name]);
        $this->createFile($path, $content, "Middleware '$name'");
    }

    private function makeRule(array $args): void
    {
        if (!isset($args[1])) {
            echo "Erro: Forneça o nome. Ex: make:rule CpfValido\n";
            exit(1);
        }

        $name = $args[1];
        $dir = __DIR__ . '/../../app/Rules';
        $path = $dir . '/' . $name . '.php';

        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $content = $this->renderTemplate('rule', ['{{name}}' => $name]);
        $this->createFile($path, $content, "Rule '$name'");
    }

    private function makeMutator(array $args): void
    {
        if (!isset($args[1])) {
            echo "Erro: Forneça o nome. Ex: make:mutator LimpaCpf\n";
            exit(1);
        }

        $name = $args[1];
        $dir = __DIR__ . '/../../app/Mutators';
        $path = $dir . '/' . $name . '.php';

        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $content = $this->renderTemplate('mutator', ['{{name}}' => $name]);
        $this->createFile($path, $content, "Mutator '$name'");
    }

    private function setupEngine(array $args): void
    {
        if (!isset($args[1]) || !in_array($args[1], ['php', 'twig'])) {
            echo "Erro: Forneça o motor. Ex: setup:engine twig\n";
            exit(1);
        }

        $engine = $args[1];
        $configFile = realpath(__DIR__ . '/../../config/app.php');
        $content = file_get_contents($configFile);

        // Troca valor da chave no config/app.php
        $content = preg_replace("/'view_engine'\s*=>\s*'[^']+'/", "'view_engine' => '$engine'", $content);
        file_put_contents($configFile, $content);

        // Limpa a tela exemplo incompatível
        $viewsPath = rtrim($this->config['paths']['views'], '/');
        if ($engine === 'twig') {
            if (file_exists("$viewsPath/home.php"))
                unlink("$viewsPath/home.php");
            echo "✅ Motor da View comutado para TWIG.\n";
            echo "   (Execute 'composer require twig/twig' no terminal se ainda não instalou!).\n";
        } else {
            if (file_exists("$viewsPath/home.twig"))
                unlink("$viewsPath/home.twig");
            echo "✅ Motor da View comutado para nativo PHP.\n";
        }
    }

    private function optimizeApp(array $args): void
    {
        echo "Iniciando otimização (Build Step)...\n";

        $routesPath = realpath(__DIR__ . '/../../routes/web.php');
        if (!file_exists($routesPath)) {
            echo "Erro: routes/web.php não encontrado.\n";
            exit(1);
        }

        $router = \Core\Routing\Router::getInstance();
        if (!$router) {
            $router = new \Core\Routing\Router();
        }

        // Importa as rotas; o arquivo web.php espera a variável $router no escopo global
        require_once $routesPath;

        // Escaneia a pasta app/Controllers buscando attributes
        $scanner = new \Core\Routing\AttributeRouteScanner();
        $scanner->scan($router, realpath(__DIR__ . '/../../app/Controllers'), 'App\\Controllers\\');

        $compiler = new \Core\Routing\RouteCompiler();
        $compiledCode = $compiler->compile($router);

        $cacheDir = __DIR__ . '/../../.cache';
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0777, true);
        }

        $cacheFile = $cacheDir . '/routes.php';
        file_put_contents($cacheFile, $compiledCode);

        echo "✅ Compilação de rotas concluída com sucesso em .cache/routes.php\n";
        echo "✅ Dependências resolvidas \033[32msem Reflection\033[0m.\n";
    }

    private function clearOptimization(array $args): void
    {
        echo "Limpando cache...\n";
        $cacheDir = __DIR__ . '/../../.cache';
        $cacheFile = $cacheDir . '/routes.php';

        if (file_exists($cacheFile)) {
            unlink($cacheFile);
            echo "✅ Cache de rotas removido com sucesso.\n";
        } else {
            echo "ℹ️ Nenhum cache encontrado para remover.\n";
        }
    }

    private function setupAuth(array $args): void
    {
        echo "Iniciando o Scaffold de Autenticação MVC...\n========================================\n";

        $baseDir = realpath(__DIR__ . '/../../');
        $authTemplatesDir = __DIR__ . '/Templates/auth';

        // 1. Controller
        $controllerDir = $this->config['paths']['controllers'] ?? $baseDir . '/app/Controllers';
        if (!is_dir($controllerDir)) mkdir($controllerDir, 0777, true);

        $controllerPath = $controllerDir . '/AuthController.php';
        if (!file_exists($controllerPath)) {
            $code = file_get_contents("$authTemplatesDir/controller.stub");
            file_put_contents($controllerPath, $code);
            echo "✅ Controller: AuthController criado.\n";
        }

        // 2. DTOs
        $dtoDir = $baseDir . '/app/DTOs/Auth';
        if (!is_dir($dtoDir)) mkdir($dtoDir, 0777, true);

        $loginDtoPath = $dtoDir . '/LoginDTO.php';
        if (!file_exists($loginDtoPath)) {
            $code = file_get_contents("$authTemplatesDir/login_dto.stub");
            file_put_contents($loginDtoPath, $code);
            echo "✅ DTO: LoginDTO criado.\n";
        }

        $registerDtoPath = $dtoDir . '/RegisterDTO.php';
        if (!file_exists($registerDtoPath)) {
            $code = file_get_contents("$authTemplatesDir/register_dto.stub");
            file_put_contents($registerDtoPath, $code);
            echo "✅ DTO: RegisterDTO criado.\n";
        }

        // 2.5 Service
        $serviceDir = $this->config['paths']['services'] ?? $baseDir . '/app/Services';
        if (!is_dir($serviceDir)) mkdir($serviceDir, 0777, true);

        $authServicePath = $serviceDir . '/AuthService.php';
        if (!file_exists($authServicePath)) {
            $code = file_get_contents("$authTemplatesDir/auth_service.stub");
            file_put_contents($authServicePath, $code);
            echo "✅ Service: AuthService criado.\n";
        }

        // 3. Model
        $modelDir = $this->config['paths']['models'] ?? $baseDir . '/app/Models';
        if (!is_dir($modelDir)) mkdir($modelDir, 0777, true);

        $modelPath = $modelDir . '/Usuario.php';
        if (!file_exists($modelPath)) {
            $code = file_get_contents("$authTemplatesDir/usuario_model.stub");
            file_put_contents($modelPath, $code);
            echo "✅ Model: Usuario criado.\n";
        }

        // 4. Migration
        $migrationDir = $this->config['paths']['migrations'] ?? $baseDir . '/database/migrations';
        if (!is_dir($migrationDir)) mkdir($migrationDir, 0777, true);

        $existing = glob($migrationDir . '/*_CreateUsuariosTable.php');
        if (empty($existing)) {
            $fileName = date('Y_m_d_His') . '_CreateUsuariosTable.php';
            $migrationPath = $migrationDir . '/' . $fileName;
            $code = file_get_contents("$authTemplatesDir/migration.stub");
            file_put_contents($migrationPath, $code);
            echo "✅ Migration: Tabela de 'usuarios' criada.\n";
        }

        // 4.9 AuthMiddleware (Verificação de Login)
        $middlewareDir = $this->config['paths']['middlewares'] ?? $baseDir . '/app/Middleware';
        if (!is_dir($middlewareDir)) mkdir($middlewareDir, 0777, true);

        $authMiddlewarePath = $middlewareDir . '/AuthMiddleware.php';
        if (!file_exists($authMiddlewarePath)) {
            $code = file_get_contents("$authTemplatesDir/auth_middleware.stub");
            file_put_contents($authMiddlewarePath, $code);
            echo "✅ Middleware: AuthMiddleware de sessão criado.\n";
        }

        // 5. Views
        $viewDir = $this->config['paths']['views'] ?? $baseDir . '/resources/views';
        $authViewDir = $viewDir . '/auth';
        if (!is_dir($authViewDir)) mkdir($authViewDir, 0777, true);

        $engine = $this->config['app']['view_engine'] ?? 'php';
        $ext = $engine === 'twig' ? '.twig' : '.php';

        $loginViewPath = $authViewDir . '/login' . $ext;
        if (!file_exists($loginViewPath)) {
            $code = file_get_contents("$authTemplatesDir/login{$ext}.stub");
            file_put_contents($loginViewPath, $code);
            echo "✅ View: Formulário de Login criado.\n";
        }

        $registerViewPath = $authViewDir . '/register' . $ext;
        if (!file_exists($registerViewPath)) {
            $code = file_get_contents("$authTemplatesDir/register{$ext}.stub");
            file_put_contents($registerViewPath, $code);
            echo "✅ View: Formulário de Registro criado.\n";
        }

        // Dashboard View
        $dashboardViewPath = $viewDir . '/dashboard' . $ext;
        if (!file_exists($dashboardViewPath)) {
            $code = file_get_contents("$authTemplatesDir/dashboard{$ext}.stub");
            file_put_contents($dashboardViewPath, $code);
            echo "✅ View: Área Restrita (Dashboard) criada.\n";
        }

        // 6. Routes
        $routesPath = $baseDir . '/routes/web.php';
        $authRoutesPath = $baseDir . '/routes/auth.php';

        if (!file_exists($authRoutesPath)) {
            $code = file_get_contents("$authTemplatesDir/routes.stub");
            file_put_contents($authRoutesPath, $code);
            echo "✅ Rotas: Arquivo auth.php criado em routes/auth.php.\n";

            // Requer o arquivo no web.php se ainda não estiver
            if (file_exists($routesPath)) {
                $routesContent = file_get_contents($routesPath);
                if (strpos($routesContent, "'auth.php'") === false && strpos($routesContent, '"auth.php"') === false) {
                    $requireSnippet = "\n\n// Inclui Rotas de Autenticação Auxiliares\nrequire_once __DIR__ . '/auth.php';\n";
                    file_put_contents($routesPath, $routesContent . $requireSnippet);
                    echo "✅ Rotas: routes/auth.php incluído automaticamente no seu routes/web.php!\n";
                }
            }
        } else {
            echo "ℹ️ O arquivo de rotas auth.php já existe.\n";
        }

        echo "\n🎉 Setup Auth concluído! Execute \033[32mphp forge migrate\033[0m para gerar o banco e acesse \033[36m/login\033[0m.\n";
    }

    private function renderTemplate(string $templateName, array $replacements): string
    {
        $templatePath = $this->config['paths']['templates'] . '/' . $templateName . '.stub';

        if (!file_exists($templatePath)) {
            echo "Erro: Template não encontrado em: $templatePath\n";
            exit(1);
        }

        $content = file_get_contents($templatePath);

        foreach ($replacements as $search => $replace) {
            $content = str_replace($search, $replace, $content);
        }

        return $content;
    }

    private function createFile(string $path, string $content, string $type): void
    {
        if (file_exists($path)) {
            echo "Erro: O $type já existe.\n";
            exit(1);
        }

        file_put_contents($path, $content);

        // Formara o caminho para exibir de forma limpa no console
        $relativePath = str_replace(realpath(__DIR__ . '/../../') . DIRECTORY_SEPARATOR, '', realpath($path) ?: $path);
        // Em casos que o arquivo seja recém criado, fallback para o caminho cru limpo
        $relativePath = str_replace('\\', '/', trim(str_replace(str_replace('\\', '/', __DIR__ . '/../../'), '', str_replace('\\', '/', $path)), '/'));

        echo "✅ $type criado em: $relativePath\n";
    }

    private function migrateRefresh(array $args): void
    {
        echo "Iniciando rollback das Migrations...\n========================\n";

        $dbConfigPath = __DIR__ . '/../../config/database.php';
        if (!file_exists($dbConfigPath)) {
            echo "Erro: Arquivo config/database.php não encontrado.\n";
            exit(1);
        }
        $dbConfigMaster = require $dbConfigPath;

        $driver = getenv('DB_CONNECTION') ?: $dbConfigMaster['default'];
        $dbConfig = $dbConfigMaster['connections'][$driver];

        $pdoApp = null;
        try {
            if ($driver === 'mysql') {
                $dsnApp = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['database']}";
                $pdoApp = new \PDO($dsnApp, $dbConfig['username'], $dbConfig['password']);
                $pdoApp->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            }
        } catch (\PDOException $e) {
            echo "Conexão falhou pular rollback (banco talvez não exista). Detalhe: " . $e->getMessage() . "\n";
        }

        if ($pdoApp) {
            try {
                // Pega as migrations na ordem reversa de execução (por batch ou id)
                $stmt = $pdoApp->query("SELECT id, migration FROM migrations ORDER BY id DESC");
                $ranMigrations = $stmt->fetchAll(\PDO::FETCH_ASSOC);

                if (!empty($ranMigrations)) {
                    $dir = $this->config['paths']['migrations'] ?? __DIR__ . '/../../database/migrations';

                    foreach ($ranMigrations as $row) {
                        $file = $row['migration'];
                        $path = $dir . '/' . $file;
                        if (file_exists($path)) {
                            $className = preg_replace('/^[0-9_]+_([a-zA-Z0-9]+)\.php$/', '$1', $file);
                            require_once $path;

                            $namespacedClass = "\\App\\Database\\Migrations\\$className";
                            if (class_exists($namespacedClass)) {
                                $className = $namespacedClass;
                            }

                            if (class_exists($className)) {
                                $migration = new $className();
                                if (method_exists($migration, 'down')) {
                                    echo "[INFO] Rollback: $file\n";
                                    $migration->down();
                                }
                            }
                        }
                    }

                    $pdoApp->exec("TRUNCATE TABLE migrations");
                    echo "\n✅ Rollback completado.\n\n";
                } else {
                    echo "Nenhuma migration rodada detectada para rollback.\n\n";
                }
            } catch (\PDOException $e) {
                echo "A tabela 'migrations' não existe ou não foi criada ainda.\n\n";
            }
        }

        // Roda up
        $this->runMigrations($args);
    }

    private function makeDto(array $args): void
    {
        if (!isset($args[1])) {
            echo "Erro: Forneça o nome. Ex: make:dto Admin/RoleDTO\n";
            exit(1);
        }

        $name = str_replace('\\', '/', $args[1]);
        if (!str_ends_with($name, 'DTO')) {
            $name .= 'DTO';
        }

        $parts = explode('/', $name);
        $className = array_pop($parts);

        $namespaceModifier = '';
        $dir = realpath(__DIR__ . '/../../') . '/app/DTOs';

        if (!empty($parts)) {
            $namespaceModifier = '\\' . implode('\\', $parts);
            $dir .= '/' . implode('/', $parts);
        }

        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $path = $dir . '/' . $className . '.php';

        $content = $this->renderTemplate('dto', [
            '{{namespaceModifier}}' => $namespaceModifier,
            '{{className}}' => $className
        ]);

        $this->createFile($path, $content, "DTO '$className'");
    }

    private function makeSeeder(array $args): void
    {
        if (!isset($args[1])) {
            echo "Erro: Forneça o nome. Ex: make:seeder DatabaseSeeder\n";
            exit(1);
        }

        $name = $args[1];
        if (!str_ends_with($name, 'Seeder')) {
            $name .= 'Seeder';
        }

        $dir = realpath(__DIR__ . '/../../') . '/database/seeders';
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $path = $dir . '/' . $name . '.php';

        $content = $this->renderTemplate('seeder', ['{{className}}' => $name]);

        $this->createFile($path, $content, "Seeder '$name'");
    }

    private function dbSeed(array $args): void
    {
        $dir = realpath(__DIR__ . '/../../') . '/database/seeders';
        if (!is_dir($dir)) {
            echo "ERRO: A pasta de seeders ($dir) não existe. Crie um seeder com make:seeder.\n";
            exit(1);
        }

        $seederName = $args[1] ?? null;

        if ($seederName) {
            $this->runSeeder($dir, $seederName);
        } else {
            if (file_exists($dir . '/DatabaseSeeder.php')) {
                $this->runSeeder($dir, 'DatabaseSeeder');
            } else {
                echo "ERRO: O arquivo DatabaseSeeder não existe na pasta seeders e nenhum outro foi definido. Ex: db:seed MeuOutroSeeder\n";
            }
        }
        echo "\n✅ Seeding concluído.\n";
    }

    private function runSeeder(string $dir, string $name): void
    {
        if (!str_ends_with($name, 'Seeder')) {
            $name .= 'Seeder';
        }

        $path = $dir . '/' . $name . '.php';
        if (!file_exists($path)) {
            echo "Erro: Seeder '$name' não encontrado em: $path\n";
            return;
        }

        require_once $path;
        $className = "\\App\\Database\\Seeders\\$name";

        if (class_exists($className)) {
            echo "[INFO] Executando seeder: $name\n";
            $seeder = new $className();
            if (method_exists($seeder, 'run')) {
                $seeder->run();
            }
        }
    }

    private function makeComponent(array $args): void
    {
        if (!isset($args[1])) {
            echo "Erro: Forneça o nome do componente. Ex: make:component tabela_usuarios\n";
            exit(1);
        }

        $name = $args[1];

        // Certifica compatibilidade de views PHP
        $engine = $this->config['app']['view_engine'] ?? 'php';
        $extension = $engine === 'twig' ? '.twig' : '.php';

        $fileName = str_ends_with($name, $extension) ? $name : $name . $extension;
        $classNameRaw = str_replace($extension, '', $fileName);

        // Preparamos a pasta 'components' dentro de 'views' globalmente
        $viewsDir = rtrim($this->config['paths']['views'], '/');
        $componentsDir = $viewsDir . '/components';

        if (!is_dir($componentsDir)) {
            mkdir($componentsDir, 0777, true);
        }

        $path = $componentsDir . '/' . $fileName;

        if (file_exists($path)) {
            echo "Erro: Componente '$fileName' já existe.\n";
            exit(1);
        }

        // Utiliza o novo template HTML .stub
        $templatePath = $this->config['paths']['templates'] . '/component.stub';
        if (!file_exists($templatePath)) {
            echo "Erro: Template não encontrado em: $templatePath\n";
            exit(1);
        }

        $content = file_get_contents($templatePath);
        $content = str_replace('{{className}}', $classNameRaw, $content);

        file_put_contents($path, $content);

        echo "✅ Componente HTMX '$fileName' criado em: app/Views/components/$fileName\n";
        echo "💡 Dica de uso na View: include('components/{$classNameRaw}') \n";
    }

    private function serveApp(array $args): void
    {
        // 1. Configurações
        $port = env('APP_PORT', 8000);
        $host = env('APP_HOST', 'localhost');
        $defaultRoute = ltrim((string)env('APP_DEFAULT_ROUTE', ''), '/');

        foreach ($args as $arg) {
            if (strpos($arg, '--port=') === 0) $port = (int) substr($arg, 7);
            if (strpos($arg, '--host=') === 0) $host = substr($arg, 7);
        }

        $fullUrl = "http://{$host}:{$port}/{$defaultRoute}";

        // 2. UI
        echo "\n";
        echo "\033[1;34m=======================================================\033[0m\n";
        echo " 🚀 \033[32mServidor de Desenvolvimento MVC Base Iniciado!\033[0m \n";
        echo "\033[1;34m=======================================================\033[0m\n";
        echo " 🔗 Acesse a aplicação clicando no link abaixo:\n";
        echo " 👉 \033[1;4;32m{$fullUrl}\033[0m\n";
        echo "-------------------------------------------------------\n";
        echo " Pressione \033[1;31mCtrl+C\033[0m para encerrar o servidor.\n\n";

        // 3. Comando (Redirecionamos stderr para stdout para ler tudo num handle só)
        $router = realpath(__DIR__ . '/../../server.php');
        $command = sprintf('php -S %s:%d %s 2>&1', $host, $port, escapeshellarg($router));

        // 4. Execução via popen (Mais estável para leitura de fluxo contínuo no Windows)
        $handle = popen($command, 'r');

        if ($handle) {
            while (!feof($handle)) {
                $line = fgets($handle);

                if ($line === false) {
                    break;
                }

                // Filtro: Se for a linha de inicialização, ignoramos
                if (stripos($line, 'Development Server') !== false && stripos($line, 'started') !== false) {
                    continue;
                }

                // Exibe o log (Respostas HTTP, Erros, etc)
                echo $line;

                // Tenta forçar a saída para o terminal imediatamente
                if (ob_get_level() > 0) ob_flush();
                flush();
            }
            pclose($handle);
        }
    }

    private function queueWork(array $args): void
    {
        $queue = $args[1] ?? 'default';
        echo "Worker iniciado para a fila: [{$queue}]\n";
        echo "Pressione Ctrl+C para parar.\n";

        while (true) {
            $job = \Core\Queue\QueueManager::pop($queue);

            if ($job) {
                echo " [" . date('Y-m-d H:i:s') . "] Processando Job: " . get_class($job) . "\n";
                try {
                    $job->handle();
                    echo " [\033[32mOK\033[0m] Job concluído com sucesso.\n";
                } catch (\Exception $e) {
                    echo " [\033[31mERRO\033[0m] Falha ao processar job: " . $e->getMessage() . "\n";
                }
            } else {
                // Dorme um pouco se não houver jobs para não estressar a CPU/Banco
                sleep(3);
            }
        }
    }

    private function setupApi(array $args): void
    {
        echo "Iniciando o Scaffold de API JWT...\n========================================\n";

        $baseDir = realpath(__DIR__ . '/../../');
        $apiTemplatesDir = __DIR__ . '/Templates/api';

        // 1. Controller
        $apiControllerDir = $baseDir . '/app/Controllers/Api';
        if (!is_dir($apiControllerDir)) mkdir($apiControllerDir, 0777, true);

        $controllerPath = $apiControllerDir . '/AuthController.php';
        if (!file_exists($controllerPath)) {
            $code = file_get_contents("$apiTemplatesDir/controller.stub");
            file_put_contents($controllerPath, $code);
            echo "✅ Controller: Api/AuthController criado.\n";
        }

        // 2. DTOs
        $dtoDir = $baseDir . '/app/DTOs/Api';
        if (!is_dir($dtoDir)) mkdir($dtoDir, 0777, true);

        $loginDtoPath = $dtoDir . '/LoginDTO.php';
        if (!file_exists($loginDtoPath)) {
            $code = file_get_contents("$apiTemplatesDir/login_dto.stub");
            file_put_contents($loginDtoPath, $code);
            echo "✅ DTO: LoginDTO criado em Api/.\n";
        }

        // 3. Service
        $serviceDir = $baseDir . '/app/Services/Api';
        if (!is_dir($serviceDir)) mkdir($serviceDir, 0777, true);

        $authServicePath = $serviceDir . '/AuthService.php';
        if (!file_exists($authServicePath)) {
            $code = file_get_contents("$apiTemplatesDir/auth_service.stub");
            file_put_contents($authServicePath, $code);
            echo "✅ Service: Api/AuthService criado.\n";
        }

        // 4. Model (Specialized User)
        $modelDir = $baseDir . '/app/Models';
        $modelPath = $modelDir . '/User.php';
        if (!file_exists($modelPath)) {
            $code = file_get_contents("$apiTemplatesDir/user_model.stub");
            file_put_contents($modelPath, $code);
            echo "✅ Model: User criado com suporte a JWT.\n";
        }

        // 5. Migration
        $migrationDir = $baseDir . '/database/migrations';
        $existing = glob($migrationDir . '/*_CreateUsersTable.php');
        if (empty($existing)) {
            $fileName = date('Y_m_d_His') . '_CreateUsersTable.php';
            $migrationPath = $migrationDir . '/' . $fileName;
            $code = file_get_contents("$apiTemplatesDir/migration.stub");
            file_put_contents($migrationPath, $code);
            echo "✅ Migration: Tabela de 'users' criada.\n";
        }

        // 6. Middleware
        $middlewareDir = $baseDir . '/app/Middleware';
        if (!is_dir($middlewareDir)) mkdir($middlewareDir, 0777, true);
        $middlewarePath = $middlewareDir . '/AuthApiMiddleware.php';
        if (!file_exists($middlewarePath)) {
            $code = file_get_contents("$apiTemplatesDir/middleware.stub");
            file_put_contents($middlewarePath, $code);
            echo "✅ Middleware: AuthApiMiddleware de JWT criado.\n";
        }

        // 7. Routes (api.php)
        $apiRoutesPath = $baseDir . '/routes/api.php';
        if (!file_exists($apiRoutesPath)) {
            $code = file_get_contents("$apiTemplatesDir/routes.stub");
            file_put_contents($apiRoutesPath, $code);
            echo "✅ Rotas: routes/api.php criado.\n";

            // Incluir no web.php
            $webRoutesPath = $baseDir . '/routes/web.php';
            if (file_exists($webRoutesPath)) {
                $webContent = file_get_contents($webRoutesPath);
                if (strpos($webContent, "'api.php'") === false && strpos($webContent, '"api.php"') === false) {
                    $snippet = "\n\n// Inclui Rotas de API\nrequire_once __DIR__ . '/api.php';\n";
                    file_put_contents($webRoutesPath, $webContent . $snippet);
                    echo "✅ Rotas: routes/api.php incluído no routes/web.php.\n";
                }
            }
        }

        echo "\n🚀 API Scaffold completa! Não esqueça de configurar o JWT_SECRET no seu .env.\n";
    }
}
