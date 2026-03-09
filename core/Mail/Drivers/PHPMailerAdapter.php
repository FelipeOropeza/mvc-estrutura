<?php

declare(strict_types=1);

namespace Core\Mail\Drivers;

use Core\Mail\MailerInterface;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

class PHPMailerAdapter implements MailerInterface
{
    private PHPMailer $mail;

    public function __construct()
    {
        $this->mail = new PHPMailer(true);
        $this->configure();
    }

    private function configure(): void
    {
        $this->mail->isSMTP();
        $this->mail->Host       = env('MAIL_HOST', 'localhost');
        $this->mail->SMTPAuth   = true;
        $this->mail->Username   = env('MAIL_USERNAME');
        $this->mail->Password   = env('MAIL_PASSWORD');
        $this->mail->SMTPSecure = env('MAIL_ENCRYPTION') === 'tls' ? PHPMailer::ENCRYPTION_STARTTLS : PHPMailer::ENCRYPTION_SMTPS;
        $this->mail->Port       = (int)env('MAIL_PORT', 587);
        $this->mail->CharSet    = 'UTF-8';

        $fromAddress = env('MAIL_FROM_ADDRESS', 'hello@example.com');
        $fromName    = env('MAIL_FROM_NAME', 'App');
        $this->mail->setFrom($fromAddress, $fromName);
    }

    public function to(string $address, string $name = ''): self
    {
        $this->mail->addAddress($address, $name);
        return $this;
    }

    public function subject(string $subject): self
    {
        $this->mail->Subject = $subject;
        return $this;
    }

    public function body(string $content, bool $isHtml = true): self
    {
        $this->mail->isHTML($isHtml);
        $this->mail->Body = $content;
        return $this;
    }

    public function send(): bool
    {
        try {
            return $this->mail->send();
        } catch (PHPMailerException $e) {
            logger()->error("Falha no envio de e-mail: " . $e->getMessage());
            if (env('APP_DEBUG', false)) {
                throw $e;
            }
            return false;
        }
    }
}
