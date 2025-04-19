<?php

declare(strict_types=1);

namespace r4ndsen\SQLite\Traits;

use r4ndsen\SQLite\Connection;
use r4ndsen\SQLite\Exception\SQLiteException;

trait ExecTrait
{
    use EscapeTrait;

    protected Connection $conn;

    /** @throws SQLiteException */
    public function exec(string $sql): bool
    {
        return $this->conn->exec($sql);
    }
}
