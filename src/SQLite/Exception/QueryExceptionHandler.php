<?php

declare(strict_types=1);

namespace r4ndsen\SQLite\Exception;

use Exception;
use r4ndsen\SQLite\Connection;
use r4ndsen\SQLite\ErrorCode;

final readonly class QueryExceptionHandler
{
    public function __construct(private Connection $conn)
    {
    }

    /** @throws SQLiteException */
    public function handle(Exception $e, string $sql = ''): never
    {
        if (str_ends_with($e->getMessage(), DatabaseMalformedException::MESSAGE)) {
            $this->throw(DatabaseMalformedException::from($e));
        }
        if (str_ends_with($e->getMessage(), 'file is not a database')) {
            $this->throw(new DatabaseException('file is not a database', previous: $e));
        }
        if (str_ends_with($e->getMessage(), 'syntax error')) {
            $this->throw(new SyntaxErrorException($sql, $e));
        }
        if (preg_match('/^Unable to (?:prepare|execute) statement: UNIQUE constraint failed/', $e->getMessage())) {
            $this->throw(UniqueConstraintException::from($e));
        }
        if (preg_match('/^Unable to prepare statement:(?: 1,)? no such table: (?<table>.+)$/', $e->getMessage(), $m)) {
            $this->throw(new TableDoesNotExistException($m['table'], $e));
        }
        if (preg_match('/^no such table: (?<table>.+)$/', $e->getMessage(), $m)) {
            $this->throw(new TableDoesNotExistException($m['table'], $e));
        }
        if (preg_match('/^Unable to prepare statement:(?: 1,)? no such column: (?<column>.+)$/', $e->getMessage(), $m)) {
            $this->throw(new ColumnDoesNotExistException($m['column'], $e));
        }
        if (preg_match('/^no such column: (?<column>.+)$/', $e->getMessage(), $m)) {
            $this->throw(new ColumnDoesNotExistException($m['column'], $e));
        }
        if (preg_match('/too many columns on (?:sqlite_altertab_)?(?<table>.+)/', $e->getMessage(), $m)) {
            $this->throw(new TooManyColumnsException($m['table'], $e));
        }
        if (preg_match('/at most \d+ tables in a join$/', $e->getMessage(), $m)) {
            $this->throw(new TooManyTablesJoinedException($sql, $e));
        }
        if (preg_match('/no such collation sequence: (?<seq>.+)$/', $e->getMessage(), $m)) {
            $this->throw(CollationDoesNotExistException::fromName($m['seq']));
        }
        if (preg_match('/no such function: (?<func>.+)/', $e->getMessage(), $m)) {
            $this->throw(FunctionDoesNotExistException::fromName($m['func']));
        }
        if (preg_match('/^error in view (?<view>[^:]+): (?<error>.*)/', $e->getMessage(), $m)) {
            $this->throw(new ViewConstraintException($m['view'], $m['error'], $e));
        }

        $this->throw(new QueryException($sql, $e));
    }

    /** @throws SQLiteException */
    private function throw(SQLiteException $e): never
    {
        $e->errorCode = ErrorCode::tryFrom($this->conn->lastExtendedErrorCode());
        throw $e;
    }
}
