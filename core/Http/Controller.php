<?php

declare(strict_types=1);

namespace Core\Http;

use Core\View\PhpEngine;
use Core\View\TwigEngine;
use Core\View\EngineInterface;

abstract class Controller
{
    private EngineInterface $engine;

    public function __construct()
    {
        $this->engine = app(EngineInterface::class);
    }

    protected function view(string $view, array $data = []): void
    {
        // Usa Singleton do container - Otimizado para Worker Mode sem I/O de disco
        $this->engine->render($view, $data);
    }
}
