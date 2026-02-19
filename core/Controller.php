<?php

namespace Core;

abstract class Controller
{
    protected function view(string $view, array $data = []): void
    {
        extract($data);
        $viewPath = __DIR__ . "/../app/Views/{$view}.php";

        if (file_exists($viewPath)) {
            require $viewPath;
        }
        else {
            echo "View {$view} not found!";
        }
    }
}
