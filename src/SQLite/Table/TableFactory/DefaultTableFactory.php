<?php

declare(strict_types=1);

namespace r4ndsen\SQLite\Table\TableFactory;

use r4ndsen\SQLite\Connection;
use r4ndsen\SQLite\Table;

class DefaultTableFactory implements TableFactoryInterface
{
    /** @var Table[] */
    protected array $tables = [];

    public function __construct(protected Connection $conn)
    {
    }

    public function loadTable(string $tableName): Table
    {
        return $this->tables[$tableName] ??= $this->createTable($tableName);
    }

    public function reset(): void
    {
        $this->tables = [];
    }

    protected function createTable(string $tableName): Table
    {
        return new Table($this->conn, $tableName);
    }
}
