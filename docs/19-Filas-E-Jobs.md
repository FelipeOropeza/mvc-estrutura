# ⛓️ 19. Filas & Jobs Assíncronos

Filas permitem que você adie tarefas demoradas (como envio de e-mails ou processamento de imagens) para melhorar a velocidade de resposta da aplicação.

## 🏗️ Criando um Job

A forma mais fácil de criar um Job é usando o comando Forge:

```bash
php forge make:job ProcessarVideo
```

Isso criará uma classe em `app/Jobs/ProcessarVideo.php`. Jobs são classes que estendem `Core\Queue\Job` e implementam o método `handle()`.

### Exemplos Práticos

#### 📸 Exemplo 1: Redimensionamento de Imagem
Jobs são perfeitos para processamento de mídia sem travar o usuário.

```php
namespace App\Jobs;

use Core\Queue\Job;
use App\Services\ImageService;

class RedimensionarFoto extends Job
{
    public function __construct(public string $path) {}

    public function handle(): void
    {
        // Serviço que faz o trabalho pesado
        $service = new ImageService();
        $service->makeThumbnail($this->path, 200, 200);
    }
}
```

#### 📊 Exemplo 2: Exportação de Relatório CSV
Se o relatório tem 50 mil linhas, você não quer que o usuário espere o PHP gerar tudo no request.

```php
namespace App\Jobs;

use Core\Queue\Job;
use App\Models\Venda;

class ExportarVendasMensais extends Job
{
    public function __construct(public int $adminId, public string $mes) {}

    public function handle(): void
    {
        $vendas = Venda::where('mes', $this->mes)->get();
        // Gera o CSV e salva no storage...
        // Notifica o admin ao terminar (via DB ou e-mail)
    }
}
```

## 🚀 Despachando para a Fila

Use o `QueueManager` ou o helper futuro para enviar o job:

```php
use Core\Queue\QueueManager;

$job = new EnviarEmailBoasVindas('user@teste.com');
QueueManager::push($job);
```

## ⚙️ Drivers Suportados

Configure o `QUEUE_DRIVER` no seu `.env`:

1. **database** (Nativo): Utiliza a tabela `jobs` no seu banco atual. Ideal para desenvolvimento simples.
2. **redis** (Alta Performance): Utiliza o Redis para gerenciar as filas. Ideal para produção.

## 👷 Rodando o Worker

Para processar as tarefas em background, você precisa deixar o worker rodando no seu terminal:

```bash
php forge queue:work
```

O worker ficará escutando a fila e executando os jobs assim que eles chegarem.
