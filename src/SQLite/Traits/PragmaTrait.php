<?php

declare(strict_types=1);

namespace r4ndsen\SQLite\Traits;

use r4ndsen\SQLite\Connection;
use r4ndsen\SQLite\Pragma;

trait PragmaTrait
{
    private Connection $conn;

    private function getPragma(): Pragma
    {
        return new Pragma($this->conn);
    }
}
