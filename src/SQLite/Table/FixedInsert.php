<?php

declare(strict_types=1);

namespace r4ndsen\SQLite\Table;

use r4ndsen\SQLite\Exception\ColumnDoesNotExistException;
use r4ndsen\SQLite\Exception\SQLiteException;

class FixedInsert extends DynamicInsert
{
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
        }

        foreach (array_keys($data) as $columnName) {
            $columnName = (string) $columnName;

            if ($this->isKnownColumn($columnName) === false) {
                throw new ColumnDoesNotExistException($columnName);
            }
        }

        return $this->insertRow($this->combineData($data));
    }
}
