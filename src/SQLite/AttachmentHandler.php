<?php

declare(strict_types=1);

namespace r4ndsen\SQLite;

use Exception;
use r4ndsen\SQLite;
use r4ndsen\SQLite\Exception\AttachedDatabaseException;
use r4ndsen\SQLite\Exception\SQLiteException;
use r4ndsen\SQLite\Traits\ExecTrait;
use r4ndsen\SQLite\Traits\PragmaTrait;
use ReflectionException;

class AttachmentHandler
{
    use ExecTrait;
    use PragmaTrait;

    /** @var array<string,SQLite> */
    protected array $connections = [];

    public function __construct(protected Connection $conn)
    {
    }

    /**
     * attach another sqlite database by its path and give it an identifier.
     *
     * @throws AttachedDatabaseException
     * @throws ReflectionException
     * @throws SQLiteException
     */
    public function attach(string $path, string $identifier): bool
    {
        if ($this->has($identifier)) {
            throw new AttachedDatabaseException(sprintf('database %s is already in use', $identifier));
        }

        try {
            $this->connections[$identifier] = new SQLite($path);

            $sql = sprintf(
                "attach '%s' as '%s'",
                $this->escape($path),
                $this->escape($identifier),
            );

            return $this->exec($sql);
        } catch (Exception $e) {
            throw AttachedDatabaseException::from($e);
        }
    }

    /** @throws AttachedDatabaseException */
    public function detach(string $identifier): bool
    {
        try {
            unset($this->connections[$identifier]);

            $sql = sprintf("detach '%s'", $this->escape($identifier));

            return $this->exec($sql);
        } catch (Exception $e) {
            throw new AttachedDatabaseException($e->getMessage());
        }
    }

    /**
     * returns a native sqlite object of an attached database.
     *
     * @throws AttachedDatabaseException
     */
    public function get(string $identifier): SQLite
    {
        return $this->connections[$identifier] ?? throw new AttachedDatabaseException('No attached sqlite with identifier: ' . $identifier);
    }

    /** returns whether a database with given identifier attached. */
    public function has(string $identifier): bool
    {
        foreach ($this->list() as $item) {
            if ($item->name === $identifier) {
                return true;
            }
        }

        return false;
    }

    protected function list(): array
    {
        return $this->getPragma()->getDatabaseList();
    }
}
