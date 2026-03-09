<?php

declare(strict_types=1);

namespace Core\Mail;

interface MailerInterface
{
    public function to(string $address, string $name = ''): self;
    public function subject(string $subject): self;
    public function body(string $content, bool $isHtml = true): self;
    public function send(): bool;
}
