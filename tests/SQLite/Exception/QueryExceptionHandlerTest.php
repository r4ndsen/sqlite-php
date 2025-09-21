<?php

namespace r4ndsen\SQLite\Exception;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use r4ndsen\SQLite\Connection;
use r4ndsen\SQLite\ErrorCode;
use RuntimeException;

final class QueryExceptionHandlerTest extends TestCase
{
    private Connection $connection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->connection = new Connection();
    }

    protected function tearDown(): void
    {
        $this->connection->close();

        parent::tearDown();
    }

    #[Test]
    #[DataProvider('providesKnownErrors')]
    public function it_maps_known_sqlite_errors(string $message, string $expectedException, ?callable $extraAssertions = null): void
    {
        $handler = new QueryExceptionHandler($this->connection);

        try {
            $handler->handle(new RuntimeException($message), 'SELECT 1');
            self::fail('Expected exception was not thrown.');
        } catch (SQLiteException $exception) {
            self::assertInstanceOf($expectedException, $exception);
            self::assertSame(ErrorCode::SQLITE_OK, $exception->errorCode);

            if ($extraAssertions !== null) {
                $extraAssertions($exception);
            }
        }
    }

    /** @return iterable<string, array{string, class-string<SQLiteException>, callable|null}> */
    public static function providesKnownErrors(): iterable
    {
        yield 'malformed database' => [
            'something ' . DatabaseMalformedException::MESSAGE,
            DatabaseMalformedException::class,
            null,
        ];

        yield 'file is not a database' => [
            'foo file is not a database',
            DatabaseException::class,
            null,
        ];

        yield 'syntax error' => [
            'whatever syntax error',
            SyntaxErrorException::class,
            static fn (SQLiteException $exception) => self::assertSame('whatever syntax error', $exception->getMessage()),
        ];

        yield 'unique constraint' => [
            'Unable to prepare statement: UNIQUE constraint failed: foo',
            UniqueConstraintException::class,
            null,
        ];

        yield 'no such table via prepare' => [
            'Unable to prepare statement: no such table: foo',
            TableDoesNotExistException::class,
            static fn (TableDoesNotExistException $exception) => self::assertSame('foo', $exception->getTable()),
        ];

        yield 'no such table direct' => [
            'no such table: bar',
            TableDoesNotExistException::class,
            static fn (TableDoesNotExistException $exception) => self::assertSame('bar', $exception->getTable()),
        ];

        yield 'no such column via prepare' => [
            'Unable to prepare statement: no such column: baz',
            ColumnDoesNotExistException::class,
            static fn (ColumnDoesNotExistException $exception) => self::assertSame('baz', $exception->getColumn()),
        ];

        yield 'no such column direct' => [
            'no such column: qux',
            ColumnDoesNotExistException::class,
            static fn (ColumnDoesNotExistException $exception) => self::assertSame('qux', $exception->getColumn()),
        ];

        yield 'too many columns' => [
            'too many columns on sqlite_altertab_users',
            TooManyColumnsException::class,
            static fn (TooManyColumnsException $exception) => self::assertSame('users', $exception->getTable()),
        ];

        yield 'too many tables in join' => [
            'at most 64 tables in a join',
            TooManyTablesJoinedException::class,
            null,
        ];

        yield 'missing collation sequence' => [
            'no such collation sequence: MY_COLLATION',
            CollationDoesNotExistException::class,
            static fn (SQLiteException $exception) => self::assertSame("Collation 'MY_COLLATION' does not exist", $exception->getMessage()),
        ];

        yield 'missing function' => [
            'no such function: MY_FUNC',
            FunctionDoesNotExistException::class,
            static fn (SQLiteException $exception) => self::assertSame("Function 'MY_FUNC' does not exist", $exception->getMessage()),
        ];

        yield 'view constraint' => [
            'error in view example_view: cannot drop column',
            ViewConstraintException::class,
            static fn (ViewConstraintException $exception) => self::assertSame('example_view', $exception->getViewName()),
        ];
    }

    #[Test]
    public function it_defaults_to_query_exception(): void
    {
        $handler = new QueryExceptionHandler($this->connection);
        $sql = 'DELETE FROM foo';
        $previous = new RuntimeException('unexpected failure');

        try {
            $handler->handle($previous, $sql);
            self::fail('Expected QueryException was not thrown.');
        } catch (QueryException $exception) {
            self::assertSame($sql, $exception->getQuery());
            self::assertSame('unexpected failure', $exception->getMessage());
            self::assertSame(ErrorCode::SQLITE_OK, $exception->errorCode);
        }
    }
}
