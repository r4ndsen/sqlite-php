<?php

declare(strict_types=1);

namespace r4ndsen\SQLite\Exception;

use Throwable;

class QueryException extends SQLiteException
{
    public function __construct(public readonly string $query, Throwable $previous)
    {
        parent::__construct($previous->getMessage(), $previous->getCode(), $previous);
    }

    public function getQuery(): string
    {
        return $this->query;
    }
}
