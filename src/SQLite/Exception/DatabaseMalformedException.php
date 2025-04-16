<?php

declare(strict_types=1);

namespace r4ndsen\SQLite\Exception;

class DatabaseMalformedException extends DatabaseException
{
    public const MESSAGE = 'database disk image is malformed';
}
