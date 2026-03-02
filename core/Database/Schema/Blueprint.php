<?php

declare(strict_types=1);

namespace Core\Database\Schema;

class Blueprint
{
    protected string $table;
    protected array $columns = [];
    protected array $primaryKeys = [];
    protected array $foreignKeys = [];
    protected array $uniqueKeys = [];

    public function __construct(string $table)
    {
        $this->table = $table;
    }

    public function id(string $name = 'id'): ColumnDefinition
    {
        $column = $this->addColumn($name, 'INT')->unsigned()->autoIncrement();
        return $column;
    }

    public function string(string $name, int $length = 255): ColumnDefinition
    {
        return $this->addColumn($name, 'VARCHAR', (string)$length);
    }

    public function text(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'TEXT');
    }

    public function integer(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'INT');
    }

    public function decimal(string $name, int $precision = 8, int $scale = 2): ColumnDefinition
    {
        return $this->addColumn($name, 'DECIMAL', (string)$precision . ',' . (string)$scale);
    }

    public function boolean(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'TINYINT', '1');
    }

    public function timestamp(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'TIMESTAMP');
    }

    public function timestamps(): void
    {
        $this->timestamp('created_at')->nullable();
        $this->timestamp('updated_at')->nullable();
    }

    protected function addColumn(string $name, string $type, ?string $length = null): ColumnDefinition
    {
        $column = new ColumnDefinition($name, $type, $length);
        $this->columns[] = $column;
        return $column;
    }

    /**
     * Define Composite Primary Keys.
     * Example: $table->primary(['role_id', 'user_id']);
     */
    public function primary($columns): void
    {
        $this->primaryKeys = is_array($columns) ? $columns : func_get_args();
    }

    /**
     * Start defining a Foreign Key.
     */
    public function foreign(string $column): ForeignKeyDefinition
    {
        $foreignKey = new ForeignKeyDefinition($column);
        $this->foreignKeys[] = $foreignKey;
        return $foreignKey;
    }

    /**
     * Builds the entire CREATE TABLE SQL string.
     */
    public function toSql(): string
    {
        $sql = "CREATE TABLE `{$this->table}` (\n";
        $definitions = [];

        // 1. Column Definitions
        foreach ($this->columns as $column) {
            $definitions[] = "    " . $column->toSql();

            // Collect single inline primaries/uniques defined dynamically via fluent interface
            if ($column->isPrimaryKey() && empty($this->primaryKeys)) {
                $definitions[] = "    PRIMARY KEY (`{$column->getName()}`)";
            }
            if ($column->isUniqueKey()) {
                $definitions[] = "    UNIQUE KEY `{$this->table}_{$column->getName()}_unique` (`{$column->getName()}`)";
            }
        }

        // 2. Composite Primary Keys
        if (!empty($this->primaryKeys)) {
            $keys = implode("`, `", $this->primaryKeys);
            $definitions[] = "    PRIMARY KEY (`{$keys}`)";
        }

        // 3. Foreign Keys
        foreach ($this->foreignKeys as $fk) {
            $definitions[] = "    " . $fk->toSql($this->table);
        }

        $sql .= implode(",\n", $definitions);
        $sql .= "\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        return $sql;
    }
}
