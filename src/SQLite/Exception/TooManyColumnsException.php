<?php

declare(strict_types=1);

namespace r4ndsen\SQLite\Exception;

use Exception;

class TooManyColumnsException extends SQLiteException
{
    public function __construct(public readonly string $table, Exception $previous)
    {
        parent::__construct(sprintf("Table '%s' has too many columns", $this->table), $previous->getCode(), $previous);
    }

    public function getTable(): string
    {
        return $this->table;
    }
}
