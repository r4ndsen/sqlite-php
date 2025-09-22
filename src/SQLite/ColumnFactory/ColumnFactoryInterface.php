<?php

declare(strict_types=1);

namespace r4ndsen\SQLite\ColumnFactory;

use r4ndsen\SQLite\Column;
use r4ndsen\SQLite\ColumnType;

interface ColumnFactoryInterface
{
    public function createColumn(string $name, ColumnType $type = ColumnType::TEXT): Column;
}
