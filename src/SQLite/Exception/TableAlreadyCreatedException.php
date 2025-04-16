<?php

declare(strict_types=1);

namespace r4ndsen\SQLite\Exception;

class TableAlreadyCreatedException extends SQLiteException
{
    public function __construct(string $table)
    {
        parent::__construct(sprintf("Table '%s' already exists", $table));
    }
}
