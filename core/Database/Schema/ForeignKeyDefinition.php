<?php

declare(strict_types=1);

namespace Core\Database\Schema;

class ForeignKeyDefinition
{
    protected string $column;
    protected string $references;
    protected string $onTable;
    protected ?string $onDelete = null;
    protected ?string $onUpdate = null;

    public function __construct(string $column)
    {
        $this->column = $column;
    }

    public function references(string $column): self
    {
        $this->references = $column;
        return $this;
    }

    public function on(string $table): self
    {
        $this->onTable = $table;
        return $this;
    }

    public function onDelete(string $action): self
    {
        $this->onDelete = $action;
        return $this;
    }

    public function onUpdate(string $action): self
    {
        $this->onUpdate = $action;
        return $this;
    }

    public function toSql(string $tableName): string
    {
        $constraintName = "{$tableName}_{$this->column}_foreign";
        $sql = "CONSTRAINT `{$constraintName}` FOREIGN KEY (`{$this->column}`) REFERENCES `{$this->onTable}` (`{$this->references}`)";

        if ($this->onDelete) {
            $sql .= " ON DELETE {$this->onDelete}";
        }

        if ($this->onUpdate) {
            $sql .= " ON UPDATE {$this->onUpdate}";
        }

        return $sql;
    }
}
