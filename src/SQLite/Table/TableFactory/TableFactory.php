<?php

declare(strict_types=1);

namespace r4ndsen\SQLite\Table\TableFactory;

use r4ndsen\SQLite\Table;

interface TableFactory
{
    public function loadTable(string $tableName): Table;

    public function reset(): void;
}
