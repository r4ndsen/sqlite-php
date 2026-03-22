<?php

declare(strict_types=1);

namespace r4ndsen\SQLite\Traits;

use Generator;
use PHPUnit\Framework\Attributes\Test;
use r4ndsen\SQLite\Column;
use r4ndsen\SQLite\Exception\ColumnDoesNotExistException;
use r4ndsen\SQLite\Exception\QueryException;
use r4ndsen\SQLite\Exception\TableDoesNotExistException;
use r4ndsen\SQLite\Exception\ViewConstraintException;
use r4ndsen\SQLite\TestCase;
use ReflectionException;
use stdClass;
use Stringable;

final class QueryTraitTest extends TestCase
{
    use QueryTrait;

    #[Test]
    public function it_should_exec_exception(): void
    {
        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('near "foo": syntax error');

        $this->SQLite->exec('foo');
    }

    #[Test]
    public function it_should_fetch_all_empty_database(): void
    {
        self::assertSame([], $this->SQLite->fetchAll('select * from sqlite_master'));
    }

    #[Test]
    public function it_should_fetch_col(): void
    {
        $table = __METHOD__;
        $table = $this->SQLite->getTable($table);
        self::assertTrue($table->addCreateColumn(Column::createIntegerColumn('id'))->create());
        $table->push(['id' => 1]);
        $table->push(['id' => 2]);

        self::assertSame([], $this->SQLite->fetchCol('select 1 where 1 = 2'));
        self::assertSame([null], $this->SQLite->fetchCol('select null'));
        self::assertSame([1], $this->SQLite->fetchCol('select 1 as foo'));

        self::assertSame([1, 2], $this->SQLite->fetchCol('select * from ' . $this->backtick(__METHOD__)));
    }

    #[Test]
    public function it_should_fetch_generators(): void
    {
        $table = __METHOD__;
        $table = $this->SQLite->getTable($table);
        self::assertTrue($table->addCreateColumn(Column::createIntegerColumn('id'))->create());
        $table->push(['id' => 1]);
        $table->push(['id' => 2]);

        self::assertInstanceOf(Generator::class, $generator = $this->SQLite->yieldAll('select * from ' . $this->backtick(__METHOD__)));

        self::assertSame(['id' => 1], $generator->current());
        $generator->next();
        self::assertSame(['id' => 2], $generator->current());

        self::assertInstanceOf(Generator::class, $generator = $this->SQLite->yieldAll('select null'));
        self::assertSame(['null' => null], $generator->current());

        self::assertInstanceOf(Generator::class, $generator = $this->SQLite->yieldAll('select 1 where 1 = 2'));
        self::assertSame([], iterator_to_array($generator));
    }

    #[Test]
    public function it_should_fetch_object(): void
    {
        $table = __METHOD__;
        $table = $this->SQLite->getTable($table);
        self::assertTrue($table->addCreateColumn(Column::createIntegerColumn('id'))->create());
        $table->push(['id' => 1]);

        self::assertNull($this->SQLite->fetchObject('select 1 where 1 = 2'));
        self::assertSame([], $this->SQLite->fetchObjects('select 1 where 1 = 2'));

        $object = $this->SQLite->fetchObject('select 1');
        self::assertInstanceOf(stdClass::class, $object);
        self::assertSame(1, $object->{1});

        $object = $this->SQLite->fetchObject('select "bar" as foo');
        self::assertInstanceOf(stdClass::class, $object);
        self::assertSame('bar', $object->foo);

        $objects = $this->SQLite->fetchObjects('select "bar" as foo');
        self::assertEquals([$object], $objects);

        $object = $this->SQLite->fetchObject('select date(:d, "localtime") as `private`', ['d' => 'now'], WithPrivateConstructor::class);
        self::assertNotNull($object);
        self::assertInstanceOf(WithPrivateConstructor::class, $object);
        self::assertSame(date('Y-m-d'), $this->getProperty($object, 'private')->getValue($object));

        $object = $this->SQLite->fetchObject('select date(:d, "localtime") as `private`', ['d' => 'now'], WithPrivateConstructor::class, ['public set']);
        self::assertNotNull($object);
        self::assertSame(date('Y-m-d'), $this->getProperty($object, 'private')->getValue($object));
        self::assertSame('public set', $this->getProperty($object, 'public')->getValue($object));
        self::assertSame(2, $this->getProperty($object, 'protected')->getValue($object));

        $object = $this->SQLite->fetchObject('select date(:d, "localtime") as `public`, "protected" as protected', ['d' => 'now'], WithPrivateConstructor::class);
        self::assertNotNull($object);
        self::assertSame(date('Y-m-d'), $this->getProperty($object, 'public')->getValue($object));
        self::assertSame('protected', $this->getProperty($object, 'protected')->getValue($object));
        self::assertSame(3, $this->getProperty($object, 'private')->getValue($object));
    }

    #[Test]
    public function it_should_fetch_object_of_abstract_class(): void
    {
        $table = __METHOD__;
        $table = $this->SQLite->getTable($table);
        self::assertTrue($table->addCreateColumn(Column::createIntegerColumn('id'))->create());
        $table->push(['id' => 1]);

        $object = $this->SQLite->fetchObject('select 1 as id', [], AbstractClass::class);
        self::assertNull($object);
    }

    #[Test]
    public function it_should_fetch_object_of_class_with_no_constructor(): void
    {
        $table = __METHOD__;
        $table = $this->SQLite->getTable($table);
        self::assertTrue($table->addCreateColumn(Column::createIntegerColumn('id'))->create());
        $table->push(['id' => 1]);

        $object = $this->SQLite->fetchObject('select "foo" as id', [], WithNoConstructor::class);
        self::assertInstanceOf(WithNoConstructor::class, $object);
        self::assertObjectHasProperty('id', $object);

        self::assertSame('foo', $object->id);
    }

    #[Test]
    public function it_should_fetch_object_of_non_existin_class(): void
    {
        $table = __METHOD__;
        $table = $this->SQLite->getTable($table);
        self::assertTrue($table->addCreateColumn(Column::createIntegerColumn('id'))->create());
        self::assertTrue($table->exists());
        $table->push(['id' => '1']);

        $this->expectException(ReflectionException::class);
        $this->expectExceptionMessage('Class "NonExisting\\Foo" does not exist');

        /** @var class-string<object> $invalidClass */
        $invalidClass = 'NonExisting\\Foo';

        $this->SQLite->fetchObject('select 1', [], $invalidClass);
    }

    #[Test]
    public function it_should_fetch_pair(): void
    {
        $table = __METHOD__;
        $table = $this->SQLite->getTable($table);
        self::assertTrue($table->addCreateColumn(Column::createIntegerColumn('id'))->create());
        $table->push(['id' => 1]);
        $table->push(['id' => 2]);

        self::assertSame(['1' => 'foobar'], $this->SQLite->fetchPair('select 1, "foobar"'));
        self::assertSame(['1.1' => 'foobar'], $this->SQLite->fetchPair('select 1.1, "foobar"'));
        self::assertSame(['1' => null], $this->SQLite->fetchPair('select 1'));
        self::assertSame(['' => 1], $this->SQLite->fetchPair('select null, 1'));
        self::assertSame(['' => null], $this->SQLite->fetchPair('select null, null'));
        self::assertSame(['' => null], $this->SQLite->fetchPair('select null'));

        self::assertSame(['1.1' => 'foobar', '2.2' => 'baz'], $this->SQLite->fetchPairs('select 1.1, "foobar" union all select 2.2, "baz"'));

        self::assertSame([], $this->SQLite->fetchPair('select 1 where 1 = 2'));
        self::assertSame([], $this->SQLite->fetchPairs('select 1 where 1 = 2'));

        self::assertSame([1 => 11], $this->SQLite->fetchPair('select rowid, id+10 as foo from ' . $this->backtick(__METHOD__)));
        self::assertSame([1 => 11, 2 => 12], $this->SQLite->fetchPairs('select rowid, id+10 as foo from ' . $this->backtick(__METHOD__)));
        self::assertSame([11 => 1, 12 => 2], $this->SQLite->fetchPairs('select id+10, id as foo from ' . $this->backtick(__METHOD__)));
    }

    #[Test]
    public function it_should_fetch_value(): void
    {
        self::assertNull($this->SQLite->fetchOne('select * from sqlite_master'));

        $table = __METHOD__;
        $table = $this->SQLite->getTable($table);

        self::assertSame(0, $this->SQLite->changes());

        self::assertTrue($table->addCreateColumn(Column::createIntegerColumn('id'))->create());
        $table->push(['id' => 1]);

        self::assertSame(1, $this->SQLite->changes());

        self::assertCount(1, $table);

        $rawData = [
            'type'     => 'table',
            'name'     => 'r4ndsen\SQLite\Traits\QueryTraitTest::it_should_fetch_value',
            'tbl_name' => 'r4ndsen\SQLite\Traits\QueryTraitTest::it_should_fetch_value',
            'rootpage' => 2,
            'sql'      => 'CREATE TABLE `r4ndsen\SQLite\Traits\QueryTraitTest::it_should_fetch_value` (`id` INTEGER default null)',
        ];

        self::assertSame($rawData, $this->SQLite->fetchOne('select * from sqlite_master'));
        self::assertSame([$rawData], $this->SQLite->fetchAll('select * from sqlite_master'));

        self::assertIsInt($this->SQLite->fetchValue('select * from `' . __METHOD__ . '`'));
        self::assertIsString($this->SQLite->fetchValue('select "1"'));
        self::assertIsInt($this->SQLite->fetchValue('select 1'));
        self::assertIsInt($this->SQLite->fetchValue('select cast("1.0" as numeric)'));
        self::assertIsFloat($this->SQLite->fetchValue('select cast("1.1" as numeric)'));
        self::assertIsInt($this->SQLite->fetchValue('select cast("1,0" as numeric)'));
        self::assertIsInt($this->SQLite->fetchValue('select cast("1,1" as numeric)'));
        self::assertIsFloat($this->SQLite->fetchValue('select cast("1,1" as real)'));
        self::assertIsFloat($this->SQLite->fetchValue('select cast("1,0" as real)'));
        self::assertIsFloat($this->SQLite->fetchValue('select cast("1.0" as real)'));
        self::assertIsInt($this->SQLite->fetchValue('select cast("1" as numeric)'));
        self::assertNull($this->SQLite->fetchValue('select null'));

        self::assertNull($this->SQLite->fetchValue('select 1 where 1 = 2'));
        self::assertNull($this->SQLite->fetchValue('select null where 1 = 2'));
    }

    #[Test]
    public function it_should_not_allow_yield_object_with_interface(): void
    {
        $instances = $this->SQLite->fetchObjects("select 'foo'", class: Stringable::class);

        self::assertSame([null], $instances);
    }

    #[Test]
    public function it_should_query_column_does_not_exist_exception(): void
    {
        $this->expectException(ColumnDoesNotExistException::class);
        $this->expectExceptionMessage("Column 'foobar' does not exist");

        try {
            $this->SQLite->query('select foobar');
        } catch (ColumnDoesNotExistException $e) {
            self::assertSame('foobar', $e->getColumn());

            throw $e;
        }
    }

    #[Test]
    public function it_should_query_single_empty_database(): void
    {
        self::assertNull($this->SQLite->querySingle('select * from sqlite_master'));
        self::assertSame([], $this->SQLite->querySingle('select * from sqlite_master', true));
    }

    #[Test]
    public function it_should_query_single_exception(): void
    {
        $this->expectException(QueryException::class);
        if ((PHP_MAJOR_VERSION === 8) && (PHP_MINOR_VERSION === 2)) {
            $this->expectExceptionMessage('Unable to prepare statement: 1, near "foo": syntax error');
        } else {
            $this->expectExceptionMessage('Unable to prepare statement: near "foo": syntax error');
        }

        $this->SQLite->querySingle('foo');
    }

    #[Test]
    public function it_should_query_table_does_not_exist_exception(): void
    {
        $this->expectException(TableDoesNotExistException::class);
        $this->expectExceptionMessage("Table 'foobar' does not exist");

        try {
            $this->SQLite->query('select * from foobar');
        } catch (TableDoesNotExistException $e) {
            self::assertSame('foobar', $e->getTable());

            throw $e;
        }
    }

    #[Test]
    public function it_should_view_on_deleted_table(): void
    {
        $this->SQLite->data->createFromArray(['id', 'title']);
        $this->SQLite->data->push(['id' => 1, 'title' => 't']);
        $this->SQLite->exec('create view testview as select * from data');

        $this->SQLite->exec('create table `tempdata` as select * from `data`');
        $this->SQLite->exec('drop table `data`');

        try {
            $this->SQLite->exec('alter table `tempdata` rename to `data`');
        } catch (ViewConstraintException $e) {
            self::assertSame('testview', $e->getViewName());
            self::assertSame('no such table: main.data', $e->getMessage());
        }
    }

    #[Test]
    public function it_should_view_on_deleted_table_with_dropping_the_view(): void
    {
        $this->SQLite->data->createFromArray(['id', 'title']);
        $this->SQLite->data->push(['id' => 1, 'title' => 't']);
        $this->SQLite->exec('create view `testview` as select * from `data`');
        $this->SQLite->exec('create table `tempdata` as select * from `data`');
        $this->SQLite->exec('drop table `data`');
        $this->SQLite->exec('drop view `testview`');
        self::assertTrue($this->SQLite->exec('alter table `tempdata` rename to `data`'));
    }

    #[Test]
    public function it_should_yield_instances(): void
    {
        $tablename = __METHOD__;
        $table = $this->SQLite->$tablename;
        self::assertTrue($table->addCreateColumn(Column::createIntegerColumn('id'))->create());
        $table->push(['id' => 1]);
        $table->push(['id' => 2]);

        $instance = $this->SQLite->fetchInstance("select * from `$tablename`");
        self::assertInstanceOf(stdClass::class, $instance);

        $instance = $this->SQLite->fetchInstance("select rowid, id+10 from `$tablename`", [], WithDebugConstructor::class);
        self::assertInstanceOf(WithDebugConstructor::class, $instance);

        // cannot instantiate an abstract class
        $instance = $this->SQLite->fetchInstance("select rowid, id+10 from `$tablename`", [], AbstractClass::class);
        self::assertNull($instance);

        // cannot instantiate a class with private constructor
        $instance = $this->SQLite->fetchInstance("select rowid, id+10 from `$tablename`", [], WithPrivateConstructor::class);
        self::assertNull($instance);

        // no rows were returned
        $instance = $this->SQLite->fetchInstance("select date('now') from `$tablename` where rowid=-1");
        self::assertNull($instance);

        // instance will be created but without giving constructor arguments
        $instance = $this->SQLite->fetchInstance("select rowid, id+10 from `$tablename`", [], WithNoConstructor::class);
        self::assertInstanceOf(WithNoConstructor::class, $instance);

        $instances = $this->SQLite->fetchInstances("select rowid, id+10 from `$tablename`", [], WithDebugConstructor::class);
        self::assertCount(2, $instances);
    }

    #[Test]
    public function it_should_yield_objects(): void
    {
        $instances = $this->SQLite->fetchObjects("select 'foo'", class: WithDebugConstructor::class);

        self::assertCount(1, $instances);
    }

    #[Test]
    public function it_should_yield_plain(): void
    {
        self::assertSame([[null, null]], $this->SQLite->fetchPlain('select null, null'));
        self::assertSame(['null' => null], $this->SQLite->fetchOne('select null, null'));

        self::assertSame([], $this->SQLite->fetchPlain('select 1 where 1 = 2'));
    }
}

class WithPrivateConstructor
{
    public static mixed $publicStaticVar;
    protected static mixed $protectedStaticVar;
    private static mixed $privateStaticVar;

    private function __construct(
        public mixed $public = 1,
        protected mixed $protected = 2,
        private mixed $private = 3,
    ) {
    }
}

abstract class AbstractClass
{
    public function __construct()
    {
    }
}

class WithNoConstructor
{
}

class WithDebugConstructor
{
    public function __construct()
    {
        // echo 'constructor of ' . static::class . ' called with ' . print_r(func_get_args(), true);
    }
}
