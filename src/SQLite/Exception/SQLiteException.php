<?php

declare(strict_types=1);

namespace r4ndsen\SQLite\Exception;

use r4ndsen\SQLite\ErrorCode;
use RuntimeException;
use Throwable;

class SQLiteException extends RuntimeException
{
    public ?ErrorCode $errorCode = null;

    public static function from(Throwable $exception): static
    {
        /** @phpstan-ignore new.static */
        return new static($exception->getMessage(), $exception->getCode(), $exception);
    }
}
