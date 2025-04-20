<?php

declare(strict_types=1);

namespace r4ndsen\SQLite;

use PHPUnit\Framework\Attributes\Test;
use r4ndsen\SQLite;
use r4ndsen\SQLite\Exception\ColumnDoesNotExistException;
use r4ndsen\SQLite\Exception\DatabaseException;
use r4ndsen\SQLite\Exception\QueryException;
use r4ndsen\SQLite\Exception\TableDoesNotExistException;
use Throwable;

final class ConnectionTest extends TestCase
{
    #[Test]
    public function it_should_database_backup(): void
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
    public function it_should_database_column_does_not_exist_exception_exec(): void
    {
        $d = new Connection(':memory:', SQLITE3_OPEN_READWRITE);

        $this->expectException(ColumnDoesNotExistException::class);
        $d->exec('select foo from sqlite_master');
    }

    #[Test]
    public function it_should_database_column_does_not_exist_exception_prepare(): void
    {
        $d = new Connection(':memory:', SQLITE3_OPEN_READWRITE);

        $this->expectException(ColumnDoesNotExistException::class);
        $d->prepare('select foo from sqlite_master');
    }

    #[Test]
    public function it_should_database_column_does_not_exist_exception_query(): void
    {
        $d = new Connection(':memory:', SQLITE3_OPEN_READWRITE);

        $this->expectException(ColumnDoesNotExistException::class);
        $d->query('select foo');
    }

    #[Test]
    public function it_should_database_column_does_not_exist_exception_query_single(): void
    {
        $d = new Connection(':memory:', SQLITE3_OPEN_READWRITE);

        $this->expectException(ColumnDoesNotExistException::class);
        $d->querySingle('select foo from sqlite_master');
    }

    #[Test]
    public function it_should_database_empty_encryption(): void
    {
        $d = new Connection(':memory:', SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE);
        self::assertInstanceOf(Connection::class, $d);
    }

    #[Test]
    public function it_should_database_incomplete_query(): void
    {
        $d = new Connection(':memory:', SQLITE3_OPEN_READWRITE);

        try {
            $d->query('select '); // incomplete input
        } catch (QueryException $e) {
            self::assertSame('select ', $e->getQuery());
        }
    }

    #[Test]
    public function it_should_database_just_open_create(): void
    {
        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessage('Unable to open database: bad parameter or other API misuse');

        new Connection(':memory:', SQLITE3_OPEN_CREATE);
    }

    #[Test]
    public function it_should_database_query_exception(): void
    {
        $d = new Connection(':memory:', SQLITE3_OPEN_READWRITE);

        $this->expectException(QueryException::class);
        $d->query('select foo(');
    }

    #[Test]
    public function it_should_database_read_only_with_open_create(): void
    {
        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessage('Unable to open database: bad parameter or other API misuse');

        new Connection(':memory:', SQLITE3_OPEN_READONLY | SQLITE3_OPEN_CREATE);
    }

    #[Test]
    public function it_should_database_table_does_not_exist_exception_exec(): void
    {
        $d = new Connection(':memory:', SQLITE3_OPEN_READWRITE);

        $this->expectException(TableDoesNotExistException::class);
        $d->exec('select * from foo');
    }

    #[Test]
    public function it_should_database_table_does_not_exist_exception_prepare(): void
    {
        $d = new Connection(':memory:', SQLITE3_OPEN_READWRITE);

        $this->expectException(TableDoesNotExistException::class);
        $d->prepare('select * from foo');
    }

    #[Test]
    public function it_should_database_table_does_not_exist_exception_query(): void
    {
        $d = new Connection(':memory:', SQLITE3_OPEN_READWRITE);

        $this->expectException(TableDoesNotExistException::class);
        $d->query('select * from foo');
    }

    #[Test]
    public function it_should_database_table_does_not_exist_exception_query_single(): void
    {
        $d = new Connection(':memory:', SQLITE3_OPEN_READWRITE);

        $this->expectException(TableDoesNotExistException::class);
        $d->querySingle('select * from foo');
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

    #[Test]
    public function it_should_query_parser_unmatched_params(): void
    {
        $d = new SQLite();

        $this->expectException(Exception\MissingParameterException::class);
        $this->expectExceptionMessage("Parameter 'foo' is missing from the bound values");
        $d->perform('select :foo');
    }

    #[Test]
    public function it_should_sq_lite_database_backup(): void
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
    public function it_should_throw_database_exception_when_file_is_not_a_database(): void
    {
        $this->expectException(DatabaseException::class);
        (new SQLite(__FILE__))->getAllTables();
    }
}
