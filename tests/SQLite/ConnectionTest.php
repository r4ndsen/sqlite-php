<?php

declare(strict_types=1);

namespace r4ndsen\SQLite;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use r4ndsen\SQLite;
use r4ndsen\SQLite\Exception\ColumnDoesNotExistException;
use r4ndsen\SQLite\Exception\DatabaseException;
use r4ndsen\SQLite\Exception\QueryException;
use r4ndsen\SQLite\Exception\SQLiteException;
use r4ndsen\SQLite\Exception\TableDoesNotExistException;
use r4ndsen\SQLite\Exception\UniqueConstraintException;
use Throwable;

final class ConnectionTest extends TestCase
{
    #[Test]
    public function it_should_backup_database(): void
    {
        $main = new SQLite();

        $table = $main->getTable('foo');
        $table->getFixedInsertTable()
            ->push(['rowid' => 1, 'id' => 1, 'data' => 'lorem'])
            ->push(['rowid' => 3, 'id' => 1, 'data' => 'lorem'])
        ;

        self::assertSame([1, 3], $table->fetchCol('select rowid from foo'));

        $main->getTable('bar')->getFixedInsertTable()->push(['my little' => 'pony'])->commit();

        $table->addIndex(new Index(Column::createDefaultColumn('data')));

        $mainSchema = $main->fetchAll('select * from sqlite_master');

        $backup = new SQLite();
        $main->getConnection()->backup($backup->getConnection());

        $backupSchema = $backup->fetchAll('select * from sqlite_master');

        self::assertSame($mainSchema, $backupSchema);

        self::assertSame([1, 3], $backup->fetchCol('select rowid from foo'));

        $backup->vacuum();

        self::assertSame([1, 3], $backup->fetchCol('select rowid from foo'));
    }

    #[Test]
    public function it_should_database_empty_encryption(): void
    {
        $d = new Connection(':memory:', SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE);
        self::assertInstanceOf(Connection::class, $d);
    }

    #[Test]
    public function it_should_database_query_exception(): void
    {
        $d = new Connection(':memory:', SQLITE3_OPEN_READWRITE);

        try {
            $d->query('select '); // incomplete input
            self::fail('The incomplete query should throw');
        } catch (QueryException $e) {
            self::assertSame('select ', $e->getQuery());
        }
    }

    #[Test]
    public function it_should_have_extended_error_codes(): void
    {
        $d = new SQLite();
        try {
            $d->querySingle('foo');
        } catch (Throwable) {
        }

        self::assertSame(1, $d->getConnection()->lastExtendedErrorCode());
        self::assertSame('near "foo": syntax error', $d->getConnection()->lastErrorMsg());
    }

    /** @param class-string<SQLiteException> $exception */
    #[Test]
    #[DataProvider('provideMissingObjectQueries')]
    public function it_should_map_missing_objects_on_every_query_method(string $method, string $sql, string $exception): void
    {
        $d = new Connection(':memory:', SQLITE3_OPEN_READWRITE);

        $this->expectException($exception);
        $d->{$method}($sql);
    }

    #[Test]
    public function it_should_query_parser_unmatched_params(): void
    {
        $d = new SQLite();

        $this->expectException(Exception\MissingParameterException::class);
        $this->expectExceptionMessage("Parameter 'foo' is missing from the bound values");
        $d->fetchValue('select :foo');
    }

    #[Test]
    #[DataProvider('provideInvalidOpenFlags')]
    public function it_should_reject_invalid_open_flags(int $flags): void
    {
        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessage('Unable to open database: bad parameter or other API misuse');

        new Connection(':memory:', $flags);
    }

    #[Test]
    public function it_should_report_extended_result_codes(): void
    {
        $conn = new Connection();
        $conn->exec('create table t (a unique)');
        $conn->exec('insert into t values (1)');

        try {
            $conn->exec('insert into t values (1)');
            self::fail('The insert should violate the unique constraint');
        } catch (UniqueConstraintException) {
            // instead of the primary result code SQLITE_CONSTRAINT
            self::assertSame(ErrorCode::SQLITE_CONSTRAINT_UNIQUE->value, $conn->lastErrorCode());
        }
    }

    #[Test]
    public function it_should_return_the_first_value_with_query_single_by_default(): void
    {
        self::assertSame(1, (new Connection())->querySingle('select 1 as a'));
    }

    #[Test]
    public function it_should_throw_database_exception_when_file_is_not_a_database(): void
    {
        $this->expectException(DatabaseException::class);
        (new SQLite(__FILE__))->getAllTables();
    }

    #[Test]
    public function it_should_vacuum_while_a_transaction_is_open(): void
    {
        $conn = new Connection();
        $conn->getTransaction()->begin();

        self::assertTrue($conn->vacuum());
        self::assertFalse($conn->getTransaction()->active());
    }

    /** @return iterable<string, array{int}> */
    public static function provideInvalidOpenFlags(): iterable
    {
        yield 'create only' => [SQLITE3_OPEN_CREATE];
        yield 'read only with create' => [SQLITE3_OPEN_READONLY | SQLITE3_OPEN_CREATE];
    }

    /** @return iterable<string, array{string, string, class-string<SQLiteException>}> */
    public static function provideMissingObjectQueries(): iterable
    {
        foreach (['exec', 'prepare', 'query', 'querySingle'] as $method) {
            yield "column via {$method}" => [$method, 'select foo from sqlite_master', ColumnDoesNotExistException::class];
            yield "table via {$method}" => [$method, 'select * from foo', TableDoesNotExistException::class];
        }
    }
}
