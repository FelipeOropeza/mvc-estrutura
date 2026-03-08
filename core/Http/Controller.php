<?php

declare(strict_types=1);

namespace Core\Http;

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
        $this->engine->render($view, $data);
    }
}
