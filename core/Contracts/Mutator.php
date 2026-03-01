<?php

declare(strict_types=1);

namespace Core\Contracts;

interface Mutator
{
    /**
     * Responsável por alterar ou higienizar um valor ANTES dele ser salvo no banco.
     * 
     * @param string $attribute O nome da coluna (ex: 'password')
     * @param mixed $value O valor atual (bruto)
     * @return mixed O valor modificado (ex: a senha embaralhada)
     */
    public function mutate(string $attribute, mixed $value): mixed;
}
