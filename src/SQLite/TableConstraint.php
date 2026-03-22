<?php

declare(strict_types=1);

namespace r4ndsen\SQLite;

use InvalidArgumentException;
use r4ndsen\SQLite\Traits\EscapeTrait;

final class TableConstraint implements CreateColumn
{
    use EscapeTrait;

    public const PRIMARY_KEY = 'primary key';
    public const UNIQUE = 'unique';

    public ?OnConflict $onConflict = null;

    /** @var Column[] */
    private array $indexedColumns = [];

    private string $key;
    private string $name;

    public function addIndexedColumn(Column $column): self
    {
        $this->indexedColumns[] = $column;

        return $this;
    }

    public function getCreateStatement(): string
    {
        if (!isset($this->key)) {
            throw new InvalidArgumentException('undefined key type, use uniqueKey() or primaryKey()');
        }

        if (!$this->indexedColumns) {
            throw new InvalidArgumentException('no index columns defined, use addIndexedColumn()');
        }

        return trim(
            sprintf(
                '%s %s (%s) %s',
                $this->name ?? '',
                $this->key,
                implode(', ', $this->indexedColumns),
                $this->onConflict?->String()
            )
        );
    }

    public function primaryKey(): self
    {
        $this->key = self::PRIMARY_KEY;

        return $this;
    }

    public function setName(string $name): self
    {
        $this->name = 'constraint ' . self::backtickIdentifier(trim($name));

        return $this;
    }

    public function uniqueKey(): self
    {
        $this->key = self::UNIQUE;

        return $this;
    }
}
