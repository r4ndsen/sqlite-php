<?php

declare(strict_types=1);

namespace r4ndsen\SQLite\Exception;

use Exception;

class TableDoesNotExistException extends SQLiteException
{
    public function __construct(public readonly string $table, Exception $previous)
    {
        parent::__construct(sprintf("Table '%s' does not exist", $table), $previous->getCode(), $previous);
    }

    public function getTable(): string
    {
        return $this->table;
    }
}
