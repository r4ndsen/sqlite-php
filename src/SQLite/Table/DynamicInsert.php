<?php

declare(strict_types=1);

namespace r4ndsen\SQLite\Table;

use r4ndsen\SQLite\Column;
use r4ndsen\SQLite\Exception\ColumnNameAmbiguousException;
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
     * @var array<string, string>
     */
    protected ?array $columnCaseMap = null;

    /** Associative array of column names in order of creation */
    protected array $pushData = [];

    /**
     * Indicates that you want to populate the rowid
     * instead of letting it auto increment.
     */
    protected bool $withRowid = false;

    /** Enhances performance massively (500% and more) */
    protected bool $withTransaction = true;

    public function deleteColumn(Column $column): bool
    {
        try {
            return parent::deleteColumn($column);
        } finally {
            $this->reset();
        }
    }

    public function drop(): bool
    {
        try {
            return parent::drop();
        } finally {
            $this->reset();
        }
    }

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

            if ($this->isKnownColumn($columnName) === false) {
                if ($this->columnExists($columnName) === false) {
                    $this->addColumn($this->getColumnFactory()->createColumn($columnName));
                    $this->seenColumn($columnName);

                    // re-initialize the prepared statement
                    $this->preparedStatement = null;
                }
            } elseif ($this->withRowid === false && Column::ROWID === $columnName) {
                $this->withRowid = true;
                $this->preparedStatement = null;
            }
        }

        if ($this->preparedStatement === null) {
            $this->initializePushData();
        }

        return parent::push($this->combineData($data));
    }

    public function renameColumn(Column $from, Column $to): bool
    {
        try {
            return parent::renameColumn($from, $to);
        } finally {
            $this->reset();
        }
    }

    protected function combineData(array $rawData): array
    {
        $pushData = $this->pushData;

        foreach ($rawData as $columnName => $value) {
            $pushData[$this->getColumnCase((string) $columnName)] = $value;
        }

        return $pushData;
    }

    protected function initializeColumnCaseMap(): array
    {
        $this->columnCaseMap = [];

        $this->seenColumn(Column::ROWID);

        foreach ($this->columns() as $column) {
            $this->seenRealColumn($column->getRaw());
        }

        return $this->columnCaseMap;
    }

    /** Prepares the pushData array with their default values in order */
    protected function initializePushData(): array
    {
        $this->pushData = [];

        if ($this->withRowid) {
            $this->pushData[Column::ROWID] = null;
        }

        foreach ($this->columns() as $column) {
            $this->pushData[$column->getRaw()] = $column->getDefaultValue();
        }

        return $this->pushData;
    }

    protected function isKnownColumn(string $columnName): bool
    {
        if (isset($this->columnCaseMap[$columnName])) {
            return true;
        }

        $lowerColumnName = $this->lower($columnName);

        if (isset($this->columnCaseMap[$lowerColumnName])) {
            $this->seenColumn($columnName);

            return true;
        }

        $trimColumnName = trim($lowerColumnName);
        if (isset($this->columnCaseMap[$trimColumnName])) {
            $this->seenColumn($columnName);

            return true;
        }

        return false;
    }

    /**
     * signalize we have come across that column.
     * this is used to store the first case occurring in the table.
     * otherwise sqlite cannot deal with multibyte column names very well.
     */
    protected function seenColumn(string $plainColumnName): void
    {
        $lowerColumnName = $this->lower($plainColumnName);

        // it's a partially known column, we saw it lowered
        if (isset($this->columnCaseMap[$lowerColumnName])) {
            $this->columnCaseMap[$plainColumnName] = $this->columnCaseMap[$lowerColumnName];

            return;
        }

        $trimColumnName = trim($lowerColumnName);

        // it's a partially known column, we saw it lowered+trimmed
        if (isset($this->columnCaseMap[$trimColumnName])) {
            $this->columnCaseMap[$plainColumnName] = $this->columnCaseMap[$trimColumnName];

            return;
        }

        // it's a fully unknown column
        $this->columnCaseMap[$lowerColumnName] = $plainColumnName;
        $this->columnCaseMap[$plainColumnName] = $plainColumnName;
        $this->columnCaseMap[$trimColumnName] = $plainColumnName;
    }

    private function getColumnCase(string $columnName): string
    {
        return $this->columnCaseMap[$columnName];
    }

    private function lower(string $s): string
    {
        return mb_strtolower($s, 'UTF-8');
    }

    private function reset(): void
    {
        $this->columnCaseMap = null;
        $this->preparedStatement = null;
        $this->pushData = [];
        $this->withRowid = false;
    }

    /** @throws ColumnNameAmbiguousException */
    private function seenRealColumn(string $columnName): void
    {
        $lowerColumnName = $this->lower($columnName);

        if (isset($this->columnCaseMap[$lowerColumnName])) {
            throw new ColumnNameAmbiguousException($columnName);
        }

        $trimColumnName = trim($lowerColumnName);
        if (isset($this->columnCaseMap[$trimColumnName])) {
            throw new ColumnNameAmbiguousException($columnName);
        }

        $this->seenColumn($columnName);
    }
}
