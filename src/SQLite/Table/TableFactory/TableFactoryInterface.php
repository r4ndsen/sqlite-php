<?php

declare(strict_types=1);

namespace r4ndsen\SQLite\Table\TableFactory;

use r4ndsen\SQLite\Table;

interface TableFactoryInterface
{
    public function loadTable(string $tableName): Table;

    public function reset(): void;
}
