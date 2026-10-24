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
        private readonly string $filename = '',
        private readonly int $flags = SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE,
        private readonly string $encryptionKey = '',
    ) {
        $this->enableExceptions(true);
        $this->connect(fn () => parent::__construct($filename, $flags, $encryptionKey));
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
            /** @phpstan-ignore return.type */
            return parent::prepare($query);
        } catch (Exception $e) {
            $this->queryExceptionHandler->handle($e, $query);
        }
    }

    /** @throws SQLiteException */
    public function query(string $query): SQLite3Result
    {
        try {
            /** @phpstan-ignore return.type */
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

    /**
     * Commits an open transaction, closes the connection and opens it again.
     * Functions, collations, aggregates and attached databases are bound to the connection and need to be registered again.
     *
     * @throws DatabaseException
     */
    public function reopen(): void
    {
        $this->getTransaction()->commit();
        $this->close();
        $this->connect(fn () => $this->open($this->filename, $this->flags, $this->encryptionKey));
    }

    // Finalize any open transaction before executing.
    public function vacuum(): bool
    {
        $this->getTransaction()->commit();

        return $this->exec('vacuum');
    }

    /**
     * Opens the database through the given callback.
     *
     * @throws DatabaseException
     */
    private function connect(callable $open): void
    {
        try {
            $open();
        } catch (Exception $e) {
            throw DatabaseException::from($e);
        }

        $this->enableExtendedResultCodes(true);
    }
}
