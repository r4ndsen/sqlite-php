<?php

declare(strict_types=1);

namespace r4ndsen\SQLite;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use r4ndsen\SQLite;
use r4ndsen\SQLite\Exception\BindValueException;
use r4ndsen\SQLite\Exception\MissingParameterException;
use r4ndsen\SQLite\Exception\QueryException;
use r4ndsen\SQLite\Exception\SyntaxErrorException;
use r4ndsen\SQLite\Exception\TableDoesNotExistException;
use stdClass;
use Stringable;

final class PreparedStatementTest extends TestCase
{
    /** @var list<string> */
    private array $dbFiles = [];

    protected function tearDown(): void
    {
        parent::tearDown();

        foreach ($this->dbFiles as $file) {
            foreach (['', '-wal', '-shm', '-journal'] as $suffix) {
                $target = $file . $suffix;
                if (file_exists($target)) {
                    @unlink($target);
                }
            }
        }

        $this->dbFiles = [];
    }

    #[Test]
    public function it_should_bind(): void
    {
        $id = Column::createIntegerColumn('id');
        $content = Column::createDefaultColumn('content');

        $this->SQLite->test
            ->addCreateColumn($id)
            ->addCreateColumn($content)
            ->createIfNotExists()
        ;

        $stm = $this->SQLite->prepare($sql = 'insert into test (content) values (?)');
        self::assertSame($sql, $stm->getQueryString());

        $stm->bind(['from prepared']);
        $stm->bind(['from prepared2'], [SQLITE3_TEXT]);

        self::assertCount(2, $this->SQLite->test);

        $stm->commit();
    }

    #[Test]
    public function it_should_bind_stringable_objects_as_text(): void
    {
        $value = new class implements Stringable {
            public function __toString(): string
            {
                return "str\0ing";
            }
        };

        self::assertSame('string', $this->SQLite->fetchValue('select :value', ['value' => $value]));
    }

    #[Test]
    public function it_should_bind_types_in_their_order_regardless_of_their_keys(): void
    {
        $stm = $this->SQLite->prepare('select typeof(?)');

        self::assertSame(['text'], $stm->bind([5], ['first' => SQLITE3_TEXT])->fetchArray(SQLITE3_NUM));
    }

    #[Test]
    public function it_should_bind_value_exception(): void
    {
        $content = Column::createDefaultColumn('content');

        $this->SQLite->test
            ->addCreateColumn($content)
            ->create()
        ;

        $stm = $this->SQLite->prepare('insert into test (content) values (:c)');

        $this->expectException(BindValueException::class);
        $stm->bindAssoc(['from prepared']);
    }

    #[Test]
    public function it_should_bind_values_with_their_native_type(): void
    {
        self::assertSame(
            ['i' => 'integer', 'f' => 'real', 't' => 'integer', 'b' => 'integer', 'n' => 'null', 's' => 'text'],
            $this->SQLite->fetchOne(
                'select typeof(:i) as i, typeof(:f) as f, typeof(:t) as t, typeof(:b) as b, typeof(:n) as n, typeof(:s) as s',
                ['i' => 5, 'f' => 1.5, 't' => true, 'b' => false, 'n' => null, 's' => '5'],
            )
        );

        $this->SQLite->exec('create table flags (done integer, raw)');
        $this->SQLite->exec('insert into flags values (0, 1)');

        self::assertSame(1, $this->SQLite->fetchValue('select count(*) from flags where done = :done', ['done' => false]));
        self::assertSame('integer', $this->SQLite->fetchValue('select typeof(raw) from flags'));
    }

    #[Test]
    public function it_should_commit_every_commit_modulo_executions(): void
    {
        $path = $this->createDatabasePath();
        $sqlite = new SQLite($path);
        $sqlite->exec('create table test (id)');

        $stm = $sqlite->prepare('insert into test (id) values (?)')
            ->activateTransaction()
            ->setCommitModulo(2)
        ;

        $stm->bind([1]);
        self::assertCount(0, (new SQLite($path))->test);

        $stm->bind([2]);
        self::assertCount(2, (new SQLite($path))->test);

        $stm->bind([3]);
        self::assertCount(2, (new SQLite($path))->test);

        $stm->bind([4]);
        self::assertCount(4, (new SQLite($path))->test);
    }

    #[Test]
    public function it_should_commit_when_deactivating_the_transaction(): void
    {
        $this->SQLite->exec('create table test (id)');
        $transaction = $this->SQLite->getConnection()->getTransaction();

        $stm = $this->SQLite->prepare('insert into test (id) values (?)')->activateTransaction();
        $stm->bind([1]);
        self::assertTrue($transaction->active());

        $stm->deactivateTransaction();
        self::assertFalse($transaction->active());
    }

    #[Test]
    public function it_should_competing_statements(): void
    {
        $id = Column::createIntegerColumn('id');

        $this->SQLite->test
            ->addCreateColumn($id)
            ->create()
        ;

        $insert = $this->SQLite->prepare('insert into test (id) values (?)');
        $insert->bind([1]);
        $insert->bind([2]);
        $insert->bind([3]);

        $truncate = $this->SQLite->prepare('delete from test where 1');
        $truncate->execute();

        self::assertTrue($this->SQLite->test->exists());
        self::assertCount(0, $this->SQLite->test);
    }

    #[Test]
    public function it_should_data_is_not_persisted_when_opening_new_instance(): void
    {
        $path = $this->createDatabasePath();
        $sqlite = new SQLite($path);
        $table = $sqlite->getTable('test')->getDynamicInsertTable();
        $table->push(['id' => '1']);
        $table->push(['title' => 'foo']);
        $table->push(['title' => 'bar']);

        // the table was created, but all rows are still part of the open transaction
        $sqlite = new SQLite($path);
        self::assertCount(0, $sqlite->test);
    }

    #[Test]
    public function it_should_data_is_persisted_after_commit(): void
    {
        $path = $this->createDatabasePath();
        $sqlite = new SQLite($path);
        $table = $sqlite->getTable('test')->getDynamicInsertTable();
        $table->push(['id' => '1']);
        $table->push(['title' => 'foo']);
        $table->push(['title' => 'bar']);
        $sqlite->getConnection()->getTransaction()->commit();

        $sqlite = new SQLite($path);
        self::assertCount(3, $sqlite->test);
    }

    #[Test]
    public function it_should_data_is_persisted_when_not_referenced_anymore(): void
    {
        $path = $this->createDatabasePath();
        $sqlite = new SQLite($path);
        $sqlite->test->getDynamicInsertTable()->push(['id' => '1']);
        $sqlite->test->getDynamicInsertTable()->push(['title' => 'foo']);
        $sqlite->test->getDynamicInsertTable()->push(['title' => 'bar']);

        $sqlite = new SQLite($path);
        self::assertCount(3, $sqlite->test);
    }

    #[Test]
    public function it_should_deactivate_an_inactive_transaction(): void
    {
        $stm = $this->SQLite->prepare('select 1');

        self::assertSame($stm, $stm->deactivateTransaction());
    }

    #[Test]
    #[DataProvider('provideArrayParameters')]
    public function it_should_insert_params(string $sql, array $parameters): void
    {
        $id = Column::createIntegerColumn('id');

        $this->SQLite->test
            ->addCreateColumn($id)
            ->create()
        ;

        $t = $this->SQLite->test;

        foreach ([null, 1, '2', '\\', false, true, 1.0] as $value) {
            $t->pushWithoutKeys([$value]);
        }
        self::assertCount(7, $t);

        // false is stored as 0
        $result = $this->SQLite->fetchCol($sql, $parameters);
        self::assertSame([1, '\\', 0, 1, 1], array_values($result));

        self::assertSame(1, $this->SQLite->fetchValue('select count(*) from test where id is null and rowid = ?', [1]));
    }

    #[Test]
    public function it_should_invalid_statements(): void
    {
        $id = Column::createIntegerColumn('id');

        $this->SQLite->test
            ->addCreateColumn($id)
            ->create()
        ;

        $insert = $this->SQLite->prepare('insert into test (id) values (');

        $this->expectException(QueryException::class);
        $insert->bind([1]);
    }

    #[Test]
    public function it_should_keep_null_bytes_in_blobs_only(): void
    {
        $statement = $this->SQLite->prepare('select :blob as blob, :text as text');
        $row = $statement->bindAssoc(['blob' => "a\0b", 'text' => "a\0b"], ['blob' => SQLITE3_BLOB])->fetchArray(SQLITE3_ASSOC);

        self::assertSame(['blob' => "a\0b", 'text' => 'ab'], $row);
    }

    #[Test]
    public function it_should_keep_the_exception_of_a_failed_prepare(): void
    {
        $this->expectException(TableDoesNotExistException::class);

        $this->SQLite->prepare('select * from missing')->execute();
    }

    #[Test]
    public function it_should_name_null_in_bind_value_exception(): void
    {
        $stm = $this->SQLite->prepare($sql = 'select :c');

        $this->expectException(BindValueException::class);
        $this->expectExceptionMessage("Could not bind value: NULL to key: '0' on: " . $sql);

        $stm->bindAssoc([null]);
    }

    #[Test]
    public function it_should_prepared_statement(): void
    {
        $id = Column::createIntegerColumn('id');
        $content = Column::createDefaultColumn('content');

        $this->SQLite->test
            ->addCreateColumn($id)
            ->addCreateColumn($content)
            ->createIfNotExists()
        ;

        $this->SQLite->test
            ->push(['content' => 'a', 'id' => 1])
            ->push(['b', 2])
            ->commit()
            ->push(['id' => 3, 'content' => 'd'])
            ->push([4, 'd'])
            ->commit()
        ;

        $stm = $this->SQLite->prepare($sql = 'insert into test (content) values (:content)');
        self::assertSame($sql, $stm->getQueryString());
        self::assertSame($sql, $stm->getSQL());
        self::assertSame($sql, $stm->getSQL(false));
        self::assertSame('insert into test (content) values (NULL)', $stm->getSQL(true));

        $stm->bindAssoc(['content' => 'foo']);
        self::assertSame($sql, $stm->getQueryString());
        self::assertSame($sql, $stm->getSQL());
        self::assertSame($sql, $stm->getSQL(false));
        self::assertSame("insert into test (content) values ('foo')", $stm->getSQL(true));

        $stm->setCommitModulo(1);

        $stm->bindAssoc(['content' => 'from prepared']);
        $stm->bindAssoc(['content' => 'from prepared2'], ['content' => SQLITE3_TEXT]);

        self::assertCount(7, $this->SQLite->test);
    }

    #[Test]
    public function it_should_set_commit_modulo_invalid_argument(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Commit Modulo needs to be > 0');

        $stm = $this->SQLite->prepare($sql = 'insert into test (content) values (:content)');
        $stm->setCommitModulo(0);
    }

    #[Test]
    public function it_should_strip_null_bytes_when_binding_strings(): void
    {
        $content = Column::createTextColumn('content');

        $this->SQLite->test
            ->addCreateColumn($content)
            ->create()
        ;

        $stm = $this->SQLite->prepare('insert into test (content) values (?)');
        $stm->bind(["12\0"]); // null byte should be removed

        self::assertSame('12', $this->SQLite->fetchValue('select content from test'));
    }

    #[Test]
    public function it_should_throw_on_non_scalar_input(): void
    {
        $id = Column::createIntegerColumn('id');

        $this->SQLite->test->addCreateColumn($id)->create();

        $this->expectException(InvalidArgumentException::class);
        $this->SQLite->test->push([new stdClass()]);
    }

    #[Test]
    public function it_should_throw_when_a_positional_parameter_is_bound_by_name(): void
    {
        $this->expectException(MissingParameterException::class);
        $this->expectExceptionMessage('Parameter 1 is missing from the bound values');

        $this->SQLite->fetchCol('select ?', ['ids' => [0, 1]]);
    }

    #[Test]
    public function it_should_throw_when_executing_an_invalid_statement(): void
    {
        $this->expectException(SyntaxErrorException::class);

        $this->SQLite->prepare('select from')->execute();
    }

    #[Test]
    public function it_should_transaction_is_committed_when_calling_count(): void
    {
        $path = $this->createDatabasePath();
        $sqlite = new SQLite($path);
        $table = $sqlite->getTable('test')->getDynamicInsertTable();
        $table->push(['id' => '1']);
        $table->push(['title' => 'foo']);
        $table->push(['title' => 'bar']);

        self::assertCount(3, $sqlite->test);
    }

    #[Test]
    public function it_should_transaction_with_push_without_keys(): void
    {
        $id = Column::createIntegerColumn('id');

        $this->SQLite->test
            ->addCreateColumn($id)
            ->create()
        ;

        $t = $this->SQLite->test;
        $t->withTransaction(true);

        $t->pushWithoutKeys([123]);

        self::assertSame(123, $t->querySingle('select id from test'));
    }

    /** @return iterable<string, array{string, array<array-key, list<int|string>>}> */
    public static function provideArrayParameters(): iterable
    {
        yield 'named' => ['select id from test where id in (:ids)', ['ids' => [0, 1, '', '\\']]];
        yield 'positional' => ['select id from test where id in (?)', [[0, 1, '', '\\']]];
    }

    private function createDatabasePath(): string
    {
        $path = sys_get_temp_dir() . '/sqlite-' . uniqid('', true) . '.sqlite';
        $this->dbFiles[] = $path;

        return $path;
    }
}
