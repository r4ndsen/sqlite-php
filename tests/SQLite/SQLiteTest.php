<?php

declare(strict_types=1);

namespace r4ndsen\SQLite;

use Closure;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use r4ndsen\SQLite;
use r4ndsen\SQLite\Exception\DatabaseMalformedException;
use r4ndsen\SQLite\Exception\SQLiteException;
use SQLite3;

final class SQLiteTest extends TestCase
{
    private const FIXTURES_DIR = __DIR__ . '/../fixtures';

    #[Test]
    public function it_should_check_pragma_with_isset_case_insensitively(): void
    {
        self::assertTrue(isset($this->SQLite->PRAGMA));
        self::assertInstanceOf(Pragma::class, $this->SQLite->PRAGMA);
    }

    #[Test]
    public function it_should_check_tables_with_isset(): void
    {
        $this->SQLite->exec('create table t (a)');

        self::assertTrue(isset($this->SQLite->t));
        self::assertTrue(isset($this->SQLite->pragma));
        self::assertFalse(isset($this->SQLite->missing));
        self::assertInstanceOf(Pragma::class, $this->SQLite->getPragma());
    }

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
    public function it_should_get_all_tables(): void
    {
        $s = new SQLite();

        $s->exec('create table foo(id int);');
        $tables = $s->getAllTables();
        self::assertCount(1, $tables);

        $s->exec('create table bar(id int);');
        $tables = $s->getAllTables();
        self::assertCount(2, $tables);

        self::assertSame('foo', $tables[0]->getName());
        self::assertSame('bar', $tables[1]->getName());
    }

    #[Test]
    public function it_should_keep_extensions_and_tables_after_refresh(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'sqlite');
        self::assertIsString($path);

        try {
            $sqlite = new SQLite($path);
            $sqlite->table->getDynamicInsertTable()->push(['id' => 1])->commit();

            $sqlite->refresh();

            self::assertSame(md5('a'), $sqlite->fetchValue("select md5('a')"));
            self::assertCount(1, $sqlite->table);
            $sqlite->table->getDynamicInsertTable()->push(['id' => 2])->commit();
            self::assertCount(2, $sqlite->table);
        } finally {
            // close the connection before deleting the file
            unset($sqlite);
            unlink($path);
        }
    }

    #[Test]
    public function it_should_load_fresh_tables_after_refresh(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'sqlite');
        self::assertIsString($path);

        try {
            $sqlite = new SQLite($path);
            $sqlite->exec('create table foo (a)');
            self::assertCount(1, $sqlite->foo->columns());

            (new SQLite($path))->exec('alter table foo add column b');
            $sqlite->refresh();

            self::assertCount(2, $sqlite->foo->columns());
        } finally {
            // close the connection before deleting the file
            unset($sqlite);
            unlink($path);
        }
    }

    #[Test]
    public function it_should_load_fresh_tables_with_get_all_tables(): void
    {
        $this->SQLite->exec('create table foo (a)');
        self::assertCount(1, $this->SQLite->foo->columns());

        $this->SQLite->exec('alter table foo add column b');

        self::assertCount(2, $this->SQLite->getAllTables()[0]->columns());
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

    /** @param Closure(SQLite): mixed $access */
    #[Test]
    #[DataProvider('provideMalformedDatabaseAccess')]
    public function it_should_throw_database_malformed_exception(Closure $access): void
    {
        $this->expectException(DatabaseMalformedException::class);

        $access(new SQLite(self::FIXTURES_DIR . '/malformed.sqlite'));
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

    /** @return iterable<string, array{Closure(SQLite): mixed}> */
    public static function provideMalformedDatabaseAccess(): iterable
    {
        yield 'exec' => [static fn (SQLite $s) => $s->exec('update data set id = 123 where 1')];
        yield 'query' => [static fn (SQLite $s) => $s->getConnection()->query('select language from data')];
        yield 'querySingle' => [static fn (SQLite $s) => $s->querySingle('select language from data')];
        yield 'validate' => [static fn (SQLite $s) => $s->validate()];
    }
}
