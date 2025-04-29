<?php

declare(strict_types=1);

namespace r4ndsen\SQLite\Table;

use r4ndsen\SQLite\Exception\ColumnDoesNotExistException;
use r4ndsen\SQLite\Exception\SQLiteException;
use r4ndsen\SQLite\Table;

class FixedInsert extends DynamicInsert
{
    protected bool $withRowid = true;

    /**
     * @throws SQLiteException
     * @throws ColumnDoesNotExistException
     */
    public function push(array $data): static
    {
        if (!isset($this->columnCaseMap)) {
            if ($this->columns() === []) {
                $this->createFromArray(array_keys($data));
            }

            $this->initializeColumnCaseMap();
            $this->initializePushData();
        }

        foreach (array_keys($data) as $columnName) {
            $columnName = (string) $columnName;

            if ($this->isKnownColumn($columnName) === false) {
                throw new ColumnDoesNotExistException($columnName);
            }
        }

        Table::push($this->combineData($data));

        return $this;
    }
}
