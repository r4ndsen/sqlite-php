<?php

declare(strict_types=1);

namespace r4ndsen\SQLite;

use BadMethodCallException;
use r4ndsen\SQLite\Traits\EscapeTrait;

class Index
{
    use EscapeTrait;

    public const UNIQUE = 'unique';

    /** @var Column[] */
    private array $indexedColumns = [];

    private string $name;
    private string $unique = '';
    private string $where = '';

    public function __construct(Column ...$columns)
    {
        foreach ($columns as $column) {
            $this->addIndexedColumn($column);
        }
    }

    public function addIndexedColumn(Column $column): self
    {
        $this->indexedColumns[] = $column;

        return $this;
    }

    /**
     * Without a name set, an index on a single column is named after its table and column (e.g. `tasks_title`),
     * as index names have to be unique across all tables of the database.
     */
    public function getCreateStatement(Table|string $table): string
    {
        if (!$this->indexedColumns) {
            throw new BadMethodCallException('No index columns defined, use addIndexedColumn()');
        }

        if (!isset($this->name) && \count($this->indexedColumns) > 1) {
            throw new BadMethodCallException('You are using multiple index columns. Please choose an index name: setName()');
        }

        $tableName = trim($table instanceof Table ? $table->getName() : $table);

        return 'create ' . trim(
            sprintf(
                '%s index if not exists %s on %s (%s) %s',
                $this->unique,
                $this->name ?? self::backtickIdentifier(mb_strtolower($tableName . '_' . $this->indexedColumns[0]->getTrimmed(), 'UTF-8')),
                self::backtickIdentifier($tableName),
                implode(', ', $this->indexedColumns),
                $this->where
            )
        );
    }

    public function setName(Column|string $nameOrColumn): self
    {
        if ($nameOrColumn instanceof Column) {
            $column = $nameOrColumn;
        } else {
            $column = Column::createDefaultColumn($nameOrColumn);
        }

        $this->name = $column->getLowerTrimmedEscaped();

        return $this;
    }

    public function setUnique(): self
    {
        $this->unique = static::UNIQUE;

        return $this;
    }

    public function setWhere(string $condition): self
    {
        $this->where = 'where ' . $condition;

        return $this;
    }
}
