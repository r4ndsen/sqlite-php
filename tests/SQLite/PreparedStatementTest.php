<?php

declare(strict_types=1);

namespace r4ndsen\SQLite;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use r4ndsen\SQLite;
use r4ndsen\SQLite\Exception\BindValueException;
use r4ndsen\SQLite\Exception\MissingParameterException;
use r4ndsen\SQLite\Exception\QueryException;
use stdClass;

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
    public function it_should_data_is_not_persisted_when_not_referenced_anymore(): void
    {
        $path = $this->createDatabasePath();
        $sqlite = new SQLite($path);
        $sqlite->getTable('test')->drop();
        $sqlite->test->getDynamicInsertTable()->push(['id' => '1']);
        $sqlite->test->getDynamicInsertTable()->push(['title' => 'foo']);
        $sqlite->test->getDynamicInsertTable()->push(['title' => 'bar']);

        $sqlite = new SQLite($path);
        self::assertCount(3, $sqlite->test);
    }

    #[Test]
    public function it_should_data_is_not_persisted_when_opening_new_instance(): void
    {
        $path = $this->createDatabasePath();
        $sqlite = new SQLite($path);
        $sqlite->getTable('test')->drop();
        $table = $sqlite->getTable('test')->getDynamicInsertTable();
        $table->push(['id' => '1']);
        $table->push(['title' => 'foo']);
        $table->push(['title' => 'bar']);

        $sqlite = new SQLite($path);
        self::assertCount(1, $sqlite->test);
    }

    #[Test]
    public function it_should_data_is_persisted_after_commit(): void
    {
        $path = $this->createDatabasePath();
        $sqlite = new SQLite($path);
        $sqlite->getTable('test')->drop();
        $table = $sqlite->getTable('test')->getDynamicInsertTable();
        $table->push(['id' => '1']);
        $table->push(['title' => 'foo']);
        $table->push(['title' => 'bar']);
        $sqlite->getConnection()->getTransaction()->commit();

        $sqlite = new SQLite($path);
        self::assertCount(3, $sqlite->test);
    }

    #[Test]
    public function it_should_data_is_persisted_after_destructing_the_prepared_statement(): void
    {
        $path = $this->createDatabasePath();
        $sqlite = new SQLite($path);
        $sqlite->getTable('test')->drop();

        $table = $sqlite->test->getDynamicInsertTable();
        $table->push(['id' => '1']);
        $table->push(['title' => 'foo']);
        $table->push(['title' => 'bar']);

        // destruct prepared statement within the table
        unset($table);

        self::assertCount(3, $sqlite->test);
    }

    #[Test]
    public function it_should_insert_params(): void
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

        $result = $this->SQLite->fetchCol('select id from test where id in (:ids)', ['ids' => [0, 1, '', '\\']]);
        self::assertSame([1, '\\', '', 1, 1], array_values($result));

        self::assertSame(1, $this->SQLite->fetchValue('select count(*) from test where id is null and rowid = ?', [1]));
    }

    #[Test]
    public function it_should_insert_params2(): void
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

        $this->expectException(MissingParameterException::class);
        $this->expectExceptionMessage('Parameter 1 is missing from the bound values');
        $this->SQLite->fetchCol('select id from test where id in (?)', ['ids' => [0, 1, '', '\\']]);
    }

    #[Test]
    public function it_should_insert_params3(): void
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
        $result = $this->SQLite->fetchCol('select id from test where id in (?)', [[0, 1, '', '\\']]);

        self::assertSame([1, '\\', '', 1, 1], array_values($result));

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
    public function it_should_set_commit_modulo(): void
    {
        $id = Column::createIntegerColumn('id');
        $content = Column::createDefaultColumn('content');

        $this->SQLite->test
            ->addCreateColumn($id)
            ->addCreateColumn($content)
            ->createIfNotExists()
        ;

        $stm = $this->SQLite->prepare($sql = 'insert into test (content) values (:content)');
        self::assertSame($sql, $stm->getQueryString());

        $stm->setCommitModulo(1);

        $stm->bindAssoc(['content' => 'from prepared']);
        $stm->bindAssoc(['content' => 'from prepared2'], ['content' => SQLITE3_TEXT]);

        self::assertCount(2, $this->SQLite->test);

        $stm->commit();
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
    public function it_should_throw_on_non_scalar_input(): void
    {
        $id = Column::createIntegerColumn('id');

        $this->SQLite->test->addCreateColumn($id)->create();

        $this->expectException(InvalidArgumentException::class);
        $this->SQLite->test->push([new stdClass()]);
    }

    #[Test]
    public function it_should_transaction_is_committed_when_calling_count(): void
    {
        $path = $this->createDatabasePath();
        $sqlite = new SQLite($path);
        $sqlite->getTable('test')->drop();
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

    private function createDatabasePath(): string
    {
        $path = sys_get_temp_dir() . '/sqlite-' . uniqid('', true) . '.sqlite';
        $this->dbFiles[] = $path;

        return $path;
    }
}
