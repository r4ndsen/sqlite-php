<?php

declare(strict_types=1);

namespace r4ndsen\SQLite\Exception;

use Throwable;

class ColumnDoesNotExistException extends SQLiteException
{
    public function __construct(public readonly string $column, ?Throwable $previous = null)
    {
        $code = $previous?->getCode();

        parent::__construct(sprintf("Column '%s' does not exist", $this->column), \is_int($code) ? $code : 0, $previous);
    }

    public function getColumn(): string
    {
        return $this->column;
    }
}
