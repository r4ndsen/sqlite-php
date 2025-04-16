<?php

declare(strict_types=1);

namespace r4ndsen\SQLite\Traits;

use r4ndsen\SQLite\Connection;
use r4ndsen\SQLite\Exception\ColumnDoesNotExistException;
use r4ndsen\SQLite\Exception\QueryException;
use r4ndsen\SQLite\Exception\TableDoesNotExistException;

trait ExecTrait
{
    use EscapeTrait;

    protected Connection $conn;

    /**
     * @throws QueryException
     * @throws TableDoesNotExistException
     * @throws ColumnDoesNotExistException
     */
    public function exec(string $sql): bool
    {
        return $this->conn->exec($sql);
    }
}
