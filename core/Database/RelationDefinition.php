<?php

declare(strict_types=1);

namespace Core\Database;

class RelationDefinition
{
    public function __construct(
        public string $type,
        public string $relatedClass,
        public string $foreignKey,
        public string $localKey
    ) {}
}
