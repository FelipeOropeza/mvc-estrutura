<?php

declare(strict_types=1);

namespace Core\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Broadcast
{
    /**
     * @param string|null $topic O tópico para o broadcast (ex: 'usuarios'). Se null, usa o nome da tabela.
     * @param string $event O evento que o app vai disparar (ex: 'refresh').
     * @param string $mode O modo de disparo ('all', 'create' ou 'update'). Padrão: 'all'.
     * @param string|array $with Nomes dos relacionamentos para carregar no broadcast (Eager Loading).
     */
    public function __construct(
        public ?string $topic = null,
        public string $event = 'refresh',
        public string $mode = 'all',
        public string|array $with = []
    ) {}
}
