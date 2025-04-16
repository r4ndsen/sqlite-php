<?php

declare(strict_types=1);

namespace r4ndsen\SQLite\Exception;

class CollationDoesNotExistException extends SQLiteException
{
    public static function fromName(string $collationName): self
    {
        return new self(sprintf("Collation '%s' does not exist", $collationName));
    }
}
