<?php

declare(strict_types=1);

namespace r4ndsen\SQLite\Exception;

use Throwable;

class ColumnNameAmbiguousException extends SQLiteException
{
    public function __construct(string $columnName, int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct('column name ' . trim($columnName) . ' is ambiguous', $code, $previous);
    }
}
