# 📧 18. Sistema de E-mails

O framework utiliza o **PHPMailer** por baixo dos panos, mas oferece uma interface fluida e simples para envio de e-mails através da abstração `Core\Mail`.

## ⚙️ Configuração

As credenciais devem ser configuradas no arquivo `.env`:

```env
MAIL_DRIVER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=seu_usuario
MAIL_PASSWORD=sua_senha
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@exemplo.com
MAIL_FROM_NAME="${APP_NAME}"
```

## ✉️ Enviando E-mails

Você pode usar o helper global `mail()` para disparar e-mails de qualquer lugar da aplicação:

```php
mail()
    ->to('cliente@destino.com', 'Nome do Cliente')
    ->subject('Assunto do E-mail')
    ->body('<h1>Olá!</h1><p>Este é um e-mail de teste.</p>')
    ->send();
```

## 🧩 Métodos Disponíveis

- `to(string $email, string $name = '')`: Define o destinatário.
- `subject(string $subject)`: Define o assunto.
- `body(string $content, bool $isHtml = true)`: Define o conteúdo.
- `attach(string $path)`: Adiciona anexos.
- `send()`: Dispara o envio e retorna `bool`.

## 💡 Dica: Templates Reais
Você pode combinar o envio de e-mail com o motor de templates (Twig ou PHP) para criar e-mails dinâmicos:

```php
$html = view('emails.boas_vindas', ['user' => $user])->getContent();

mail()
    ->to($user->email)
    ->subject('Bem-vindo!')
    ->body($html)
    ->send();
```
