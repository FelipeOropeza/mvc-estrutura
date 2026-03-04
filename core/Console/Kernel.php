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
            case 'optimize':
                $this->optimizeApp($args);
                break;
            case 'optimize:clear':
                $this->clearOptimization($args);
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
        echo "  make:service <Nome>      Cria um novo Service de regra de negócio para injetar. Ex: UserService\n";
        echo "  make:migration <Nome>    Cria uma nova Migration de Banco de Dados. Ex: CreateUsersTable\n";
        echo "  make:middleware <Nome>   Cria um novo Middleware de validação. Ex: AuthMiddleware\n";
        echo "  make:rule <Nome>         Cria um atributo de Validação customizado. Ex: CpfValido\n";
        echo "  make:mutator <Nome>      Cria um atributo de Mutação customizado. Ex: LimpaCpf\n";
        echo "  migrate                  Gera o Banco de Dados ausente (se possível) e roda as Migrations\n";
        echo "  setup:engine <php|twig>  Muda o motor padrão do projeto e limpa views não utilizadas\n";
        echo "  setup:auth               Instala o scaffolding completo de Autenticação (Login, Registro, DB)\n";
        echo "  optimize                 Compila as rotas e dependências para máxima performance\n";
        echo "  optimize:clear           Remove os arquivos de cache compilados\n";
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
}
