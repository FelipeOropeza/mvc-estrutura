<?php

declare(strict_types=1);

namespace Core\Mail;

use Core\Mail\Drivers\PHPMailerAdapter;

class MailManager
{
    /**
     * Retorna o driver padrão de e-mail.
     */
    public static function driver(): MailerInterface
    {
        // Por enquanto retornamos sempre o PHPMailer, mas no futuro podemos ter LogDriver pra dev
        return new PHPMailerAdapter();
    }

    /**
     * Atalho para enviar um e-mail simples.
     */
    public static function send(string $to, string $subject, string $body): bool
    {
        return self::driver()->to($to)->subject($subject)->body($body)->send();
    }
}
