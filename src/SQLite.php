<?php

declare(strict_types=1);

namespace r4ndsen;

use r4ndsen\SQLite\AttachmentHandler;
use r4ndsen\SQLite\Connection;
use r4ndsen\SQLite\Exception\AttachedDatabaseException;
use r4ndsen\SQLite\Exception\DatabaseMalformedException;
use r4ndsen\SQLite\Exception\SQLiteException;
use r4ndsen\SQLite\Extensions\Aggregates;
use r4ndsen\SQLite\Extensions\Collations;
use r4ndsen\SQLite\Extensions\Functions;
use r4ndsen\SQLite\Pragma;
use r4ndsen\SQLite\Table;
use r4ndsen\SQLite\Table\TableFactory\DefaultTableFactory;
use r4ndsen\SQLite\Table\TableFactory\TableFactory;
use r4ndsen\SQLite\Traits\PragmaTrait;
use r4ndsen\SQLite\Traits\QueryTrait;
use ReflectionException;

class SQLite
{
    use PragmaTrait;
    use QueryTrait;

    private ?AttachmentHandler $attachmentHandler = null;
    private ?TableFactory $tableFactory = null;

    public function __construct(
        public readonly string $path = ':memory:',
        public readonly int $options = SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE,
    ) {
        $this->connect();
        $this->init();
    }

    public function __get(string $tableName): Pragma|Table
    {
        if (strtolower($tableName) === 'pragma') {
            return $this->getPragma();
        }

        return $this->getTable($tableName);
    }

    /**
     * Attach another sqlite database and give it an identifier.
     *
     * @throws ReflectionException
     */
    public function attach(string $path, string $identifier): bool
    {
        return $this->getAttachmentHandler()->attach($path, $identifier);
    }

    /**
     * Detach an attached database.
     *
     * @throws AttachedDatabaseException
     */
    public function detach(string $identifier): bool
    {
        return $this->getAttachmentHandler()->detach($identifier);
    }

    /** @return Table[] */
    public function getAllTables(): array
    {
        $tables = [];
        $this->getTableFactory()->reset();
        foreach ($this->getPragma()->getTableNames() as $tableName) {
            $tables[] = $this->getTableFactory()->loadTable($tableName);
        }

        return $tables;
    }

    /**
     * Get sqlite object of attached database.
     *
     * @throws AttachedDatabaseException
     */
    public function getAttached(string $identifier): self
    {
        return $this->getAttachmentHandler()->get($identifier);
    }

    public function getConnection(): Connection
    {
        return $this->conn;
    }

    public function getTable(string $tableName): Table
    {
        return $this->getTableFactory()->loadTable($tableName);
    }

    public function refresh(): void
    {
        $this->connect();
        $this->getTableFactory()->reset();
        $this->attachmentHandler = null;
    }

    public function setTableFactory(TableFactory $tableFactory): self
    {
        $this->tableFactory = $tableFactory;

        return $this;
    }

    public function vacuum(): bool
    {
        return $this->conn->vacuum();
    }

    /**
     * @throws SQLiteException
     * @throws DatabaseMalformedException
     */
    public function validate(): void
    {
        $result = $this->getPragma()->integrityCheck();

        if ($result === DatabaseMalformedException::MESSAGE) {
            throw new DatabaseMalformedException($result);
        }

        if ($result !== Pragma::INTEGRITY_CHECK_OKAY) {
            throw new SQLiteException($result ?? 'integrity check failed');
        }
    }

    private function connect(): void
    {
        $this->conn = new Connection($this->path, $this->options);
    }

    private function getAttachmentHandler(): AttachmentHandler
    {
        return $this->attachmentHandler ??= new AttachmentHandler($this->conn);
    }

    private function getTableFactory(): TableFactory
    {
        return $this->tableFactory ??= new DefaultTableFactory($this->conn);
    }

    private function init(): void
    {
        $this->loadExtensions();
    }

    private function loadExtensions(): void
    {
        (new Functions($this->conn))->registerDefaults();
        (new Collations($this->conn))->registerDefaults();
        (new Aggregates($this->conn))->registerDefaults();
    }
}
