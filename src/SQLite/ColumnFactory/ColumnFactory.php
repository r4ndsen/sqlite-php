<?php

declare(strict_types=1);

namespace r4ndsen\SQLite\ColumnFactory;

use r4ndsen\SQLite\Column;
use r4ndsen\SQLite\ColumnType;

readonly class ColumnFactory implements ColumnFactoryInterface
{
    public function __construct(public mixed $defaultValue = '')
    {
    }

    public function createColumn(string $name, ColumnType $type = ColumnType::TEXT): Column
    {
        return new Column($name, $type, $this->defaultValue);
    }
}
