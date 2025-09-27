<?php

declare(strict_types=1);

namespace r4ndsen\SQLite;

use BadMethodCallException;

class Index
{
    public const UNIQUE = 'unique';

    private string $genericName;
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
        if (!isset($this->genericName)) {
            $this->setGenericName($column);
        }

        $this->indexedColumns[] = $column;

        return $this;
    }

    public function getCreateStatement(): string
    {
        if (!$this->indexedColumns) {
            throw new BadMethodCallException('No index columns defined, use addIndexedColumn()');
        }

        if (!isset($this->name) && \count($this->indexedColumns) > 1) {
            throw new BadMethodCallException('You are using multiple index columns. Please choose an index name: setName()');
        }

        return 'create ' . trim(
            sprintf(
                '%s index if not exists %s on %%s (%s) %s',
                $this->unique,
                $this->getName(),
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

    private function getName(): string
    {
        return $this->name ?? $this->genericName;
    }

    private function setGenericName(Column $column): self
    {
        $this->genericName = $column->getLowerTrimmedEscaped();

        return $this;
    }
}
