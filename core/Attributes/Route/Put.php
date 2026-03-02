<?php

declare(strict_types=1);

namespace Core\Attributes\Route;

#[\Attribute(\Attribute::TARGET_METHOD)]
class Put
{
    public function __construct(public string $uri) {}
}
