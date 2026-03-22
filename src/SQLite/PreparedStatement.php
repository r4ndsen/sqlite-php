<?php

declare(strict_types=1);

namespace r4ndsen\SQLite;

use Exception;
use InvalidArgumentException;
use r4ndsen\SQLite\Exception\BindValueException;
use r4ndsen\SQLite\Exception\SQLiteException;
use SQLite3Result;
use SQLite3Stmt;
use Stringable;

final class PreparedStatement
{
    private int $commitModulo = 10_000;
    private int $counter = 0;
    private SQLite3Stmt $stm;
    private ?Transaction $tx = null;

    public function __construct(
        private readonly Connection $conn,
        private readonly string $sql,
    ) {
    }

    public function __destruct()
    {
        $this->commit();
    }

    /**
     * Activate transactions before bulk inserting
     * Only one transaction can be active at a time.
     */
    public function activateTransaction(): self
    {
        $this->tx = $this->conn->getTransaction();

        return $this;
    }

    /**
     * bind params from an indexed array to the sql statement.
     *
     * @throws BindValueException
     * @throws InvalidArgumentException
     * @throws SQLiteException
     */
    public function bind(array $data, array $types = []): SQLite3Result
    {
        $types = array_values($types);
        $i = 0;
        foreach ($data as $value) {
            // binding starts at index "1"
            $this->_bind($i + 1, $value, $types[$i] ?? SQLITE3_TEXT);
            $i++;
        }

        return $this->execute();
    }

    /**
     * bind params from an associated array to the sql statement.
     *
     * @throws BindValueException
     * @throws InvalidArgumentException
     * @throws SQLiteException
     */
    public function bindAssoc(array $data, array $types = []): SQLite3Result
    {
        foreach ($data as $key => $value) {
            $this->_bind((string) $key, $value, $types[$key] ?? SQLITE3_TEXT);
        }

        return $this->execute();
    }

    // In case a transaction is active: trigger commit.
    public function commit(): void
    {
        $this->tx?->commit();
    }

    public function deactivateTransaction(): self
    {
        if ($this->tx?->active()) {
            $this->tx->commit();
        }

        $this->tx = null;

        return $this;
    }

    /** @throws SQLiteException */
    public function execute(): SQLite3Result
    {
        if ($this->tx === null) {
            return $this->executeStatement();
        }

        $this->tx->begin();

        try {
            return $this->executeStatement();
        } finally {
            if (++$this->counter % $this->commitModulo === 0) {
                $this->tx->commit()->begin();
                $this->counter = 0;
            }
        }
    }

    public function getQueryString(): string
    {
        return $this->sql;
    }

    /** @see https://www.php.net/manual/de/sqlite3stmt.getsql.php  */
    public function getSQL(bool $withBoundValues = false): string
    {
        try {
            return $this->getStatement()->getSQL($withBoundValues);
        } catch (Exception) {
            return '';
        }
    }

    public function setCommitModulo(int $modulo): self
    {
        if ($modulo <= 0) {
            throw new InvalidArgumentException('Commit Modulo needs to be > 0');
        }

        $this->commitModulo = $modulo;

        return $this;
    }

    /**
     * @throws BindValueException
     * @throws InvalidArgumentException
     */
    private function _bind(int|string $param, mixed $value, int $type): void
    {
        if (\is_string($value) || $value instanceof Stringable) {
            $value = str_replace("\0", '', (string) $value);
        }

        if ($value !== null && !\is_scalar($value)) {
            throw new InvalidArgumentException('Bind value must be scalar');
        }

        if ($this->getStatement()->bindValue($param, $value, $type) === false) {
            throw new BindValueException(sprintf("Could not bind value: %s to key: '%s' on: %s", $value === null ? 'NULL' : sprintf("'%s'", (string) $value), $param, $this->sql));
        }
    }

    /** @throws SQLiteException */
    private function executeStatement(): SQLite3Result
    {
        try {
            /** @phpstan-ignore return.type */
            return $this->getStatement()->execute();
        } catch (SQLiteException $e) {
            throw $e;
        } catch (Exception $e) {
            $this->conn->queryExceptionHandler->handle($e, $this->getSQL());
        }
    }

    // Initializes and returns the sqlite statement
    private function getStatement(): SQLite3Stmt
    {
        return $this->stm ??= $this->conn->prepare($this->sql);
    }
}
