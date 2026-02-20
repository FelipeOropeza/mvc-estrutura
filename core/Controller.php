<?php

namespace Core;

use Core\View\PhpEngine;
use Core\View\TwigEngine;
use Core\View\EngineInterface;

abstract class Controller
{
    private EngineInterface $engine;

    public function __construct()
    {
        $config = require __DIR__ . '/../config/app.php';

        $viewPath = $config['paths']['views'];
        $engineType = $config['app']['view_engine'] ?? 'php';

        if ($engineType === 'twig') {
            $this->engine = new TwigEngine($viewPath);
        }
        else {
            $this->engine = new PhpEngine($viewPath);
        }
    }

    protected function view(string $view, array $data = []): void
    {
        $this->engine->render($view, $data);
    }
}
