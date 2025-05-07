<?php

declare(strict_types=1);

namespace r4ndsen\SQLite\Extensions;

use r4ndsen\SQLite\Connection;

abstract class AbstractExtension
{
    public function __construct(protected Connection $conn)
    {
    }

    abstract public function registerDefaults(): void;
}
