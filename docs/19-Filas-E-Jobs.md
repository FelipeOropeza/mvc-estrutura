# ⛓️ 19. Filas & Jobs Assíncronos

Filas permitem que você adie tarefas demoradas (como envio de e-mails ou processamento de imagens) para melhorar a velocidade de resposta da aplicação.

## 🏗️ Criando um Job

Jobs são classes que estendem `Core\Queue\Job`. Elas devem implementar o método `handle()`.

```php
namespace App\Jobs;

use Core\Queue\Job;

class EnviarEmailBoasVindas extends Job
{
    public function __construct(public string $email) {}

    public function handle(): void
    {
        // Lógica demorada aqui
        mail()->to($this->email)->subject('Bem-vindo')->send();
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
