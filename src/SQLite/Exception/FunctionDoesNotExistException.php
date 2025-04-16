<?php

declare(strict_types=1);

namespace r4ndsen\SQLite\Exception;

class FunctionDoesNotExistException extends SQLiteException
{
    public static function fromName(string $functionName): self
    {
        return new self(sprintf("Function '%s' does not exist", $functionName));
    }
}
