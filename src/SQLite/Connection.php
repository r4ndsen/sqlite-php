<?php

declare(strict_types=1);

namespace r4ndsen\SQLite;

use Exception;
use r4ndsen\SQLite\Exception\DatabaseException;
use r4ndsen\SQLite\Exception\QueryExceptionHandler;
use r4ndsen\SQLite\Exception\SQLiteException;
use SQLite3;
use SQLite3Result;
use SQLite3Stmt;

/** @internal */
final class Connection extends SQLite3
{
    public QueryExceptionHandler $queryExceptionHandler;
    private Transaction $tx;

    /** @throws DatabaseException */
    public function __construct(
        string $filename = '',
        int $flags = SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE,
        string $encryptionKey = '',
    ) {
        $this->enableExceptions(true);

        try {
            parent::__construct($filename, $flags, $encryptionKey);
        } catch (Exception $e) {
            throw DatabaseException::from($e);
        }

        $this->enableExtendedResultCodes(true);
        $this->queryExceptionHandler = new QueryExceptionHandler($this);
    }

    /** @throws SQLiteException */
    public function exec(string $query): bool
    {
        try {
            return parent::exec($query);
        } catch (Exception $e) {
            $this->queryExceptionHandler->handle($e, $query);
        }
    }

    public function getTransaction(): Transaction
    {
        return $this->tx ??= new Transaction($this);
    }

    /** @throws SQLiteException */
    public function prepare(string $query): SQLite3Stmt
    {
        try {
            // @phpstan-ignore return.type
            return parent::prepare($query);
        } catch (Exception $e) {
            $this->queryExceptionHandler->handle($e, $query);
        }
    }

    /** @throws SQLiteException */
    public function query(string $query): SQLite3Result
    {
        try {
            // @phpstan-ignore return.type
            return parent::query($query);
        } catch (Exception $e) {
            $this->queryExceptionHandler->handle($e, $query);
        }
    }

    /**
     * Returns the value of the first column of results or an array of the entire first row (if entireRow is TRUE).
     * If the query is valid but no results are returned, then NULL will be returned if entireRow is FALSE, otherwise an empty array is returned.
     * Invalid or failing queries will return FALSE.
     *
     * @see fetchValue() for a more advanced usage
     *
     * @return array|bool|float|int|string|null
     *
     * @throws SQLiteException
     */
    public function querySingle(string $query, bool $entireRow = false): mixed
    {
        try {
            return parent::querySingle($query, $entireRow);
        } catch (Exception $e) {
            $this->queryExceptionHandler->handle($e, $query);
        }
    }

    /** Finalized any open transaction before executing. */
    public function vacuum(): bool
    {
        $this->getTransaction()->commit();

        return $this->exec('vacuum');
    }
}
