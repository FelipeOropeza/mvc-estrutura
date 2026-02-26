<?php

declare(strict_types=1);

namespace Core\Database\Schema;

class ColumnDefinition
{
    protected string $name;
    protected string $type;
    protected ?string $length;

    // Properties for chainable methods
    protected bool $isNullable = false;
    protected bool $isUnsigned = false;
    protected bool $isAutoIncrement = false;
    protected bool $isPrimary = false;
    protected bool $isUnique = false;
    protected $defaultValue = null;

    public function __construct(string $name, string $type, ?string $length = null)
    {
        $this->name = $name;
        $this->type = $type;
        $this->length = $length;
    }

    public function nullable(): self
    {
        $this->isNullable = true;
        return $this;
    }

    public function unsigned(): self
    {
        $this->isUnsigned = true;
        return $this;
    }

    public function autoIncrement(): self
    {
        $this->isAutoIncrement = true;
        $this->isPrimary = true; // By default AI implies Primary Key
        return $this;
    }

    public function unique(): self
    {
        $this->isUnique = true;
        return $this;
    }

    public function primary(): self
    {
        $this->isPrimary = true;
        return $this;
    }

    public function default ($value): self
    {
        $this->defaultValue = $value;
        return $this;
    }

    /**
     * Translates this object's state into a SQL string.
     */
    public function toSql(): string
    {
        $sql = "`{$this->name}` {$this->type}";

        if ($this->length !== null && !in_array($this->type, ['TEXT', 'DATE', 'DATETIME', 'TIMESTAMP', 'INT', 'TINYINT'])) {
            $sql .= "({$this->length})";
        }

        if ($this->isUnsigned) {
            $sql .= " UNSIGNED";
        }

        if (!$this->isNullable) {
            $sql .= " NOT NULL";
        }

        if ($this->defaultValue !== null) {
            if (is_string($this->defaultValue)) {
                $sql .= " DEFAULT '{$this->defaultValue}'";
            }
            else {
                $sql .= " DEFAULT {$this->defaultValue}";
            }
        }

        if ($this->isAutoIncrement) {
            $sql .= " AUTO_INCREMENT";
        }

        return $sql;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isPrimaryKey(): bool
    {
        return $this->isPrimary;
    }

    public function isUniqueKey(): bool
    {
        return $this->isUnique;
    }
}
