<?php

declare(strict_types=1);

namespace r4ndsen\SQLite;

use Exception;
use r4ndsen\SQLite;
use r4ndsen\SQLite\Exception\AttachedDatabaseException;
use r4ndsen\SQLite\Exception\DatabaseException;
use r4ndsen\SQLite\Exception\SQLiteException;
use r4ndsen\SQLite\Traits\ExecTrait;
use r4ndsen\SQLite\Traits\PragmaTrait;

class AttachmentHandler
{
    use ExecTrait;
    use PragmaTrait;

    /**
     * Separate connections to the attached databases, opened on first use.
     *
     * @var array<string, SQLite>
     */
    private array $connections = [];

    /**
     * Paths of the attached databases by their lowercased identifier.
     *
     * @var array<string, string>
     */
    private array $paths = [];

    public function __construct(private Connection $conn)
    {
    }

    /**
     * attach another sqlite database by its path and give it an identifier.
     *
     * @throws AttachedDatabaseException
     * @throws SQLiteException
     */
    public function attach(string $path, string $identifier): bool
    {
        // SQLite itself refuses an identifier already in use
        try {
            $sql = sprintf(
                "attach '%s' as '%s'",
                $this->escape($path),
                $this->escape($identifier),
            );

            $result = $this->exec($sql);
        } catch (Exception $e) {
            throw AttachedDatabaseException::from($e);
        }

        $this->paths[strtolower($identifier)] = $path;

        return $result;
    }

    /** @throws AttachedDatabaseException */
    public function detach(string $identifier): bool
    {
        try {
            $sql = sprintf("detach '%s'", $this->escape($identifier));

            $result = $this->exec($sql);
        } catch (Exception $e) {
            throw AttachedDatabaseException::from($e);
        }

        unset($this->paths[strtolower($identifier)], $this->connections[strtolower($identifier)]);

        return $result;
    }

    /**
     * returns a separate sqlite object for an attached database.
     *
     * @throws AttachedDatabaseException
     * @throws DatabaseException
     */
    public function get(string $identifier): SQLite
    {
        $key = strtolower($identifier);
        $path = $this->paths[$key] ?? throw new AttachedDatabaseException('No attached sqlite with identifier: ' . $identifier);

        // a second connection to an in-memory database would open a new, empty database
        if ($path === '' || $path === ':memory:') {
            throw new AttachedDatabaseException(sprintf('The in-memory database %s can only be used through the connection it is attached to', $identifier));
        }

        return $this->connections[$key] ??= new SQLite($path);
    }

    // Returns whether a database with given identifier attached.
    public function has(string $identifier): bool
    {
        foreach ($this->list() as $item) {
            if ($item->name === $identifier) {
                return true;
            }
        }

        return false;
    }

    private function list(): array
    {
        return $this->getPragma()->getDatabaseList();
    }
}
