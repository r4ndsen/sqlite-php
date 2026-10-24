<?php

declare(strict_types=1);

namespace r4ndsen\SQLite\Table;

use r4ndsen\SQLite\Column;
use r4ndsen\SQLite\Exception\ColumnNameAmbiguousException;
use r4ndsen\SQLite\Exception\SQLiteException;
use r4ndsen\SQLite\Table;

/**
 * Automatically creates the table.
 * Dynamically creates columns.
 * Creates and updates prepares statements.
 * Handles transactions.
 */
class DynamicInsert extends Table
{
    /**
     * preserves the original case a column appeared in.
     *
     * @var array<string, string>|null
     */
    protected ?array $columnCaseMap = null;

    // Enhances performance massively (500% and more)
    protected bool $withTransaction = true;

    public function deleteColumn(Column $column): bool
    {
        try {
            return parent::deleteColumn($column);
        } finally {
            $this->reset();
        }
    }

    /** @throws SQLiteException */
    public function drop(): bool
    {
        try {
            return parent::drop();
        } finally {
            $this->reset();
        }
    }

    /** @throws SQLiteException */
    public function push(array $data): static
    {
        if (!isset($this->columnCaseMap)) {
            if ($this->columns() === []) {
                $this->createFromArray(array_keys($data));
            }

            $this->initializeColumnCaseMap();
        }

        foreach (array_keys($data) as $columnName) {
            $columnName = (string) $columnName;

            if ($this->isKnownColumn($columnName) === false && $this->columnExists($columnName) === false) {
                $column = $this->getColumnFactory()->createColumn($columnName);
                $this->addColumn($column);

                // the column is created with its trimmed name
                $this->seenColumn($column->getTrimmed());
                $this->seenColumn($columnName);
            }
        }

        return $this->insertRow($this->combineData($data));
    }

    public function renameColumn(Column $from, Column $to): bool
    {
        try {
            return parent::renameColumn($from, $to);
        } finally {
            $this->reset();
        }
    }

    /**
     * Maps the keys of the row to the real column names.
     * Columns missing in the row are left out, so SQLite applies their default value.
     */
    protected function combineData(array $rawData): array
    {
        $row = [];
        foreach ($rawData as $columnName => $value) {
            $row[$this->columnCaseMap[$columnName] ?? $columnName] = $value;
        }

        return $row;
    }

    /** @return array<string, string> */
    protected function initializeColumnCaseMap(): array
    {
        $this->columnCaseMap = [];

        $this->seenColumn(Column::ROWID);

        foreach ($this->columns() as $column) {
            $this->seenRealColumn($column->getRaw());
        }

        return $this->columnCaseMap ?? [];
    }

    /** @throws SQLiteException */
    protected function insertRow(array $row): static
    {
        return $this->insert(array_keys($row), $row);
    }

    protected function isKnownColumn(string $columnName): bool
    {
        if (!isset($this->columnCaseMap[$columnName])) {
            // it may be a new spelling of a known column
            if (!isset($this->columnCaseMap[trim($this->lower($columnName))])) {
                return false;
            }

            $this->seenColumn($columnName);
        }

        return true;
    }

    protected function reset(): void
    {
        $this->columnCaseMap = null;
        $this->commit();
    }

    /**
     * signalize we have come across that column.
     * this is used to store the first case occurring in the table.
     * otherwise sqlite cannot deal with multibyte column names very well.
     *
     * A column is stored by its plain, lowered and lowered+trimmed name,
     * so whenever a lowered name is known, its trimmed form points to the same column.
     */
    protected function seenColumn(string $plainColumnName): void
    {
        $lowerColumnName = $this->lower($plainColumnName);
        $trimColumnName = trim($lowerColumnName);

        // it's a partially known column, we saw it lowered and/or trimmed
        if (isset($this->columnCaseMap[$trimColumnName])) {
            $this->columnCaseMap[$plainColumnName] = $this->columnCaseMap[$trimColumnName];

            return;
        }

        // it's a fully unknown column
        $this->columnCaseMap[$lowerColumnName] = $plainColumnName;
        $this->columnCaseMap[$plainColumnName] = $plainColumnName;
        $this->columnCaseMap[$trimColumnName] = $plainColumnName;
    }

    /** @throws ColumnNameAmbiguousException */
    protected function seenRealColumn(string $columnName): void
    {
        if (isset($this->columnCaseMap[trim($this->lower($columnName))])) {
            throw new ColumnNameAmbiguousException($columnName);
        }

        $this->seenColumn($columnName);
    }

    private function lower(string $s): string
    {
        return mb_strtolower($s, 'UTF-8');
    }
}
