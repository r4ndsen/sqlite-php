<?php

declare(strict_types=1);

namespace r4ndsen\SQLite;

use PHPUnit\Framework\Attributes\Test;
use r4ndsen\SQLite;
use r4ndsen\SQLite\Exception\DatabaseMalformedException;
use r4ndsen\SQLite\Exception\SQLiteException;
use SQLite3;

final class SQLiteTest extends TestCase
{
    private const FIXTURES_DIR = __DIR__ . '/../fixtures';

    #[Test]
    public function it_should_commit_transaction_upon_reset(): void
    {
        $table = $this->SQLite->table->getDynamicInsertTable();
        $table->push(['id' => 1]);

        self::assertTrue($this->SQLite->getConnection()->getTransaction()->active());
        $this->SQLite->refresh();
        self::assertFalse($this->SQLite->getConnection()->getTransaction()->active());
    }

    #[Test]
    public function it_should_database_malformed_exception_on_exec(): void
    {
        $this->expectException(DatabaseMalformedException::class);
        $s = new SQLite(self::FIXTURES_DIR . '/malformed.sqlite');
        $s->exec('update data set id = 123 where 1');
    }

    #[Test]
    public function it_should_database_malformed_exception_on_query(): void
    {
        $this->expectException(DatabaseMalformedException::class);
        $s = new SQLite(self::FIXTURES_DIR . '/malformed.sqlite');
        $s->getConnection()->query('select language from data');
    }

    #[Test]
    public function it_should_database_malformed_exception_on_query_single(): void
    {
        $this->expectException(DatabaseMalformedException::class);
        $s = new SQLite(self::FIXTURES_DIR . '/malformed.sqlite');
        $s->querySingle('select language from data');
    }

    #[Test]
    public function it_should_get_all_tables(): void
    {
        $s = new SQLite();

        $s->exec('create table foo(id int);');
        $tables = $s->getAllTables();
        self::assertCount(1, $tables);

        $s->exec('create table bar(id int);');
        $tables = $s->getAllTables();
        self::assertCount(2, $tables);

        self::assertSame('foo', current($tables)->getName());
        self::assertSame('bar', end($tables)->getName());
    }

    #[Test]
    public function it_should_memory_database(): void
    {
        $sqlite = new SQLite(':memory:');
        self::assertSame(':memory:', $sqlite->path);

        $sqlite = new SQLite();
        self::assertSame(':memory:', $sqlite->path);
        self::assertInstanceOf(SQLite3::class, $sqlite->getConnection());
    }

    #[Test]
    public function it_should_native_command_vacuum(): void
    {
        self::assertTrue($this->SQLite->vacuum());
    }

    #[Test]
    public function it_should_null_character(): void
    {
        $s = new SQLite();
        $s->data->getDynamicInsertTable()
            ->push(['id' => "\0"])
            ->push(['id' => "\0" . '1'])
            ->push(['id' => "\0 1"])
            ->push(['id' => "\0 1\0"])
            ->push(['id' => "1 \0"])
            ->push(['id' => "2 \0" . '1'])
            ->push(['id' => "3 \0 1"])
            ->push(['id' => "4 \0 1\0"])
            ->commit()
        ;

        self::assertSame(
            [
                '',
                '1',
                ' 1',
                ' 1',
                '1 ',
                '2 1',
                '3  1',
                '4  1',
            ],
            $s->fetchCol('select id from data')
        );
    }

    #[Test]
    public function it_should_sq_lite_validate_malformed(): void
    {
        $this->expectException(DatabaseMalformedException::class);
        $s = new SQLite(self::FIXTURES_DIR . '/malformed.sqlite');
        $s->validate();
    }

    #[Test]
    public function it_should_valid_database(): void
    {
        $s = new SQLite(self::FIXTURES_DIR . '/valid.sqlite');
        self::assertSame(Pragma::INTEGRITY_CHECK_OKAY, $s->pragma->integrityCheck());
    }

    #[Test]
    public function it_should_validate_fragmentation(): void
    {
        $this->expectException(SQLiteException::class);
        $this->expectExceptionMessageMatches(
            "#^\*{3} in database main \*{3}\r?\n(On tree|Tree 2) page 2 cell 0: Rowid 9 out of order\r?\nFragmentation of 1 bytes reported as 0 on page 2$#m"
        );

        $s = new SQLite(self::FIXTURES_DIR . '/fragmentation.sqlite');
        $s->validate();
    }
}
