<?php

declare(strict_types=1);

namespace Core\Attributes\Route;

#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class Middleware
{
    public function __construct(public string|array $class) {}
}
