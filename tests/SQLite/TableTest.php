<?php

declare(strict_types=1);

namespace r4ndsen\SQLite;

use BadMethodCallException;
use Exception;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use r4ndsen\SQLite;
use r4ndsen\SQLite\Exception\CreateTableFailedException;
use r4ndsen\SQLite\Exception\FeatureNotSupportedException;
use r4ndsen\SQLite\Exception\SyntaxErrorException;
use r4ndsen\SQLite\Exception\TableAlreadyCreatedException;
use r4ndsen\SQLite\Exception\TableDoesNotExistException;
use r4ndsen\SQLite\Exception\TooManyTablesJoinedException;
use RuntimeException;
use SQLite3;

final class TableTest extends TestCase
{
    #[Test]
    public function delete_column_on_non_existing_table_via_perform(): void
    {
        if (SQLite3::version()['versionNumber'] < 3_035_000) {
            $this->expectException(SyntaxErrorException::class);
        } else {
            $this->expectException(TableDoesNotExistException::class);
        }

        $this->SQLite->perform('alter table `foo` drop column `bar`');
    }

    #[Test]
    public function it_should_add_column(): void
    {
        @unlink($tempdb = '/tmp/temp.sqlite');
        $this->SQLite = new SQLite($tempdb);
        $t = $this->SQLite->table;
        $t->addCreateColumn(Column::createDefaultColumn('test'));
        $t->create();

        self::assertCount(1, $t->columns());

        $t->addColumn(Column::createDefaultColumn('id'));

        self::assertFalse($t->addColumn(Column::createDefaultColumn('TEST')));

        $columns = $t->columns();
        self::assertCount(2, $columns);

        $t
            ->push(['test' => 'a', 'id' => '1'])
            ->push(['b', '2'])
            ->push(['c', '3'])
            ->push(['d', '4'])
            ->push(['e', '5'])
            ->commit()
        ;

        self::assertSame(5, $t->maxRow());
        self::assertSame(5, $t->lastInsertRowID());
        self::assertCount(5, $t);

        self::assertSame([
            ['test' => 'a', 'id' => '1'],
            ['test' => 'b', 'id' => '2'],
            ['test' => 'c', 'id' => '3'],
            ['test' => 'd', 'id' => '4'],
            ['test' => 'e', 'id' => '5'],
        ], $t->toArray());

        self::assertSame([
            'a' => '1',
            'b' => '2',
            'c' => '3',
            'd' => '4',
            'e' => '5',
        ], $this->SQLite->fetchPairs('select test, id from `table`'));

        self::assertTrue($t->truncate());
        self::assertSame(0, $t->maxRow());
        self::assertCount(0, $t);

        // rowid gets removed aswell when truncating
        $t
            ->push(['test' => 'a', 'id' => 1])
            ->push(['b', 2])
            ->commit()
        ;
        self::assertSame(2, $t->maxRow());

        $t->exec('delete from `table` where rowid = 1');
        self::assertSame(1, $t->changes());

        self::assertSame(2, $t->maxRow());
        self::assertCount(1, $t);
        foreach ($t as $row) {
            self::assertSame(['test' => 'b', 'id' => '2'], $row);
        }

        $this->SQLite = new SQLite($tempdb);
        self::assertTrue($this->SQLite->table->exists());
    }

    #[Test]
    public function it_should_add_column_add_index(): void
    {
        $this->expectException(BadMethodCallException::class);
        $this->SQLite->table->addIndex(new Index());
    }

    #[Test]
    public function it_should_add_column_no_exist(): void
    {
        $this->expectException(TableDoesNotExistException::class);
        self::assertFalse($this->SQLite->table->addColumn(Column::createDefaultColumn('test')));
    }

    #[Test]
    public function it_should_add_constraint(): void
    {
        $t = $this->SQLite->table;

        $column = Column::createDefaultColumn('test');
        $constraint = new TableConstraint();
        $constraint->addIndexedColumn($column);
        $constraint->uniqueKey();
        self::assertSame('UNIQUE (`test`)', $constraint->getCreateStatement());

        $constraint->primaryKey();
        self::assertSame('PRIMARY KEY (`test`)', $constraint->getCreateStatement());

        $t->addCreateColumn($column);
        $t->addConstraint($constraint);

        $t->create();
    }

    #[Test]
    public function it_should_add_constraint_add_index2(): void
    {
        $C = Column::createDefaultColumn('test');

        $Index = new Index();
        $Index
            ->addIndexedColumn($C)
            ->setUnique()
            ->setWhere('1=2')
        ;

        $this->SQLite->table
            ->addCreateColumn($C)
            ->create()
        ;

        self::assertTrue($this->SQLite->table->addIndex($Index));
    }

    #[Test]
    public function it_should_add_constraint_no_index_columns(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $t = $this->SQLite->table;

        $column = Column::createDefaultColumn('test');
        $constraint = new TableConstraint();
        $constraint->uniqueKey();

        $t->addCreateColumn($column);
        $t->addConstraint($constraint);

        $t->create();
    }

    #[Test]
    public function it_should_add_constraint_no_key(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $t = $this->SQLite->table;

        $column = Column::createDefaultColumn('test');
        $constraint = new TableConstraint();
        $constraint->addIndexedColumn($column);

        $t->addCreateColumn($column);
        $t->addConstraint($constraint);

        $t->create();
    }

    #[Test]
    public function it_should_add_constraint_set_conflict_clause(): void
    {
        $t = $this->SQLite->table;

        $column = Column::createDefaultColumn('test');
        $constraint = new TableConstraint();
        $constraint->addIndexedColumn($column);
        $constraint->uniqueKey();
        self::assertSame('UNIQUE (`test`)', $constraint->getCreateStatement());

        $constraint->onConflict = OnConflict::IGNORE;

        $constraint->setName(' my constraint ');
        self::assertSame('CONSTRAINT `my constraint` UNIQUE (`test`) ON CONFLICT IGNORE', $constraint->getCreateStatement());

        $t->addCreateColumn($column);
        $t->addConstraint($constraint);

        $t->create();
    }

    #[Test]
    public function it_should_add_create_column(): void
    {
        $t = $this->SQLite->table;
        $t->addCreateColumn(Column::createDefaultColumn('test'));
        $t->addCreateColumn(Column::createTextColumn('test2', null));
        $t->addCreateColumn(Column::createTextColumn('test3')->disallowNull());

        self::assertTrue($t->create());
        self::assertTrue($t->createIfNotExists());

        $columns = $t->columns();
        $c = current($columns);

        self::assertCount(3, $columns);
        self::assertInstanceOf(Column::class, $c);

        self::assertSame(0, $c->getColumnId());
        self::assertFalse($c->getPk());

        self::assertSame($c, $t->getColumnByColumnId(0));
        self::assertSame($c, $t->getColumnByName('test'));
    }

    #[Test]
    public function it_should_add_create_column_duplicate(): void
    {
        $this->expectException(Exception::class);

        $t = $this->SQLite->table;
        $t->addCreateColumn(Column::createDefaultColumn('test'));
        $t->addCreateColumn(Column::createDefaultColumn('test'));
        $t->create();
        $t->createIfNotExists();
    }

    #[Test]
    public function it_should_add_create_column_no_columns(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $t = $this->SQLite->table;
        $t->create();
    }

    #[Test]
    public function it_should_columns_with_single_quote(): void
    {
        $tablename = "my ' table";
        $t = $this->SQLite->{$tablename};
        $column = Column::createDefaultColumn($name = "my ' column");

        $t->addCreateColumn($column);
        self::assertTrue($t->create());

        $t->push([$name => $value = "test ' val"])->commit();

        $row = $t->toArray()[0];
        self::assertSame([$name => $value], $row);

        $t->pushWithoutKeys(['foo']);

        self::assertCount(2, $t);
    }

    #[Test]
    public function it_should_create_allow_transaction(): void
    {
        $t = $this->SQLite->table;

        self::assertFalse($this->getPropertyValue($t, 'withTransaction'));

        $t->withTransaction(true);

        self::assertTrue($this->getPropertyValue($t, 'withTransaction'));
    }

    #[Test]
    public function it_should_create_already_exists(): void
    {
        $t = $this->SQLite->table;

        $column = Column::createDefaultColumn('test');

        $t->addCreateColumn($column);

        self::assertTrue($t->create());

        $this->expectException(TableAlreadyCreatedException::class);
        $this->expectExceptionMessage("Table 'table' already exists");

        $t->create();
    }

    #[Test]
    public function it_should_create_from_array(): void
    {
        $t = $this->SQLite->table;

        self::assertTrue($t->createFromArray(['a']));

        $this->expectException(TableAlreadyCreatedException::class);
        $this->expectExceptionMessage("Table 'table' already exists");

        $t->createFromArray(['b']);
    }

    #[Test]
    public function it_should_create_from_array_with_zero_index(): void
    {
        $t = $this->SQLite->table;

        self::assertTrue($t->createFromArray(['foo', 0, 1, 'bar']));

        self::assertCount(4, $t->columns());
    }

    #[Test]
    public function it_should_create_table_with_reserved_name(): void
    {
        $table = $this->SQLite->getTable('sqlite_test');

        self::assertFalse($table->exists());
        self::assertCount(0, $table);

        $this->expectException(CreateTableFailedException::class);
        $table->createFromArray(['foo']);
    }

    #[Test]
    public function it_should_create_table_with_reserved_sensitive_name(): void
    {
        $table = $this->SQLite->getTable('SQLite_test');

        self::assertFalse($table->exists());
        self::assertCount(0, $table);

        $this->expectException(CreateTableFailedException::class);
        $table->createFromArray(['foo']);
    }

    #[Test]
    public function it_should_custom_column_factory(): void
    {
        $table = $this->SQLite->test->getDynamicInsertTable()->setColumnFactory($this->getCustomColumnFactory());
        $table->push(['id' => '1']);
        $table->push(['title' => '2']);

        self::assertSame([
            ['id' => '1', 'title' => 'foobar'],
            ['id' => 'foobar', 'title' => '2'],
        ], $table->toArray());
    }

    #[Test]
    public function it_should_delete_column_on_non_existing_table(): void
    {
        if (SQLite3::version()['versionNumber'] < 3_035_000) {
            $this->expectException(FeatureNotSupportedException::class);
        } else {
            $this->expectException(TableDoesNotExistException::class);
        }

        $this->SQLite
            ->getTable('foo')
            ->deleteColumn(
                Column::createDefaultColumn('bar')
            )
        ;
    }

    #[Test]
    public function it_should_drop_table(): void
    {
        self::assertTrue($this->SQLite->table->drop());
    }

    #[Test]
    public function it_should_last_insert_row_id_with_multiple_tables(): void
    {
        $t = $this->SQLite->table->getDynamicInsertTable();
        $t->push(['test' => 'a', 'id' => '1'])
            ->push(['test' => 'b', 'id' => '2'])
            ->push(['test' => 'c', 'id' => '3'])
            ->push(['test' => 'd', 'id' => '4'])
            ->push(['test' => 'e', 'id' => '5'])
            ->commit()
        ;

        self::assertSame(5, $this->SQLite->lastInsertRowID());

        $t2 = $this->SQLite->table2->getDynamicInsertTable();
        $t2->push(['test' => 'a', 'id' => '1'])->commit();
        self::assertSame(1, $this->SQLite->lastInsertRowID());

        $t->push(['test' => 'f', 'id' => '6'])->commit();
        self::assertSame(6, $this->SQLite->lastInsertRowID());
    }

    #[Test]
    public function it_should_last_insert_row_id_with_no_data_inserted(): void
    {
        self::assertSame(0, $this->SQLite->lastInsertRowID());
    }

    #[Test]
    public function it_should_last_insert_row_id_with_vacuum(): void
    {
        $t = $this->SQLite->table->getDynamicInsertTable();
        $t->push(['test' => 'a', 'id' => '1'])
            ->push(['test' => 'b', 'id' => '2'])
            ->push(['test' => 'c', 'id' => '3'])
            ->push(['test' => 'd', 'id' => '4'])
            ->push(['test' => 'e', 'id' => '5'])
        ;

        self::assertSame(5, $this->SQLite->lastInsertRowID());

        $t->exec('delete from `table` where rowid > 3');
        self::assertSame(5, $this->SQLite->lastInsertRowID());
        self::assertSame(3, $this->SQLite->querySingle('select max(rowid) from `table`'));
    }

    #[Test]
    public function it_should_multi_join_tables(): void
    {
        $tables = range(1, 100);

        $sql = 'select * from `1`';

        foreach ($tables as $table) {
            $this->SQLite->$table->getDynamicInsertTable()->push(['id' => 1, 'table id' => $table]);

            if (1 === $table) {
                continue;
            }

            $sql .= sprintf(' join `%1$s` on `%1$s`.id = `1`.id', $table) . PHP_EOL;
        }

        try {
            $this->SQLite->fetchOne($sql);
        } catch (TooManyTablesJoinedException $e) {
            self::assertMatchesRegularExpression('/Unable to prepare statement:( 1,)? at most 64 tables in a join/', $e->getMessage());
        }

        try {
            $this->SQLite->exec($sql);
        } catch (TooManyTablesJoinedException $e) {
            self::assertSame('at most 64 tables in a join', $e->getMessage());
        }

        try {
            $this->SQLite->query($sql);
        } catch (TooManyTablesJoinedException $e) {
            self::assertMatchesRegularExpression('/Unable to prepare statement:( 1,)? at most 64 tables in a join/', $e->getMessage());
        }

        try {
            $this->SQLite->querySingle($sql);
        } catch (TooManyTablesJoinedException $e) {
            self::assertMatchesRegularExpression('/Unable to prepare statement:( 1,)? at most 64 tables in a join/', $e->getMessage());
        }
    }

    #[Test]
    public function it_should_table_general_methods(): void
    {
        self::assertInstanceOf(Table::class, $this->SQLite->table);
        self::assertSame('table', $this->SQLite->table->getName());
        self::assertFalse($this->SQLite->table->exists());
        self::assertCount(0, $this->SQLite->table);
        self::assertSame(0, $this->SQLite->table->maxRow());
        self::assertSame([], $this->SQLite->table->columns());
        self::assertSame([], $this->invokeArgs($this->SQLite->table, 'schema', []));
        self::assertNull($this->SQLite->table->getColumnByName('test'));
        self::assertNull($this->SQLite->table->getColumnByColumnId(0));
        self::assertTrue($this->SQLite->table->drop());
    }

    #[Test]
    public function it_should_table_to_array_when_table_does_not_exist(): void
    {
        $this->expectException(TableDoesNotExistException::class);
        $this->SQLite->foo->toArray();
    }

    #[Test]
    public function it_should_throw_on_delete_column_when_not_supported(): void
    {
        $testDouble = new class($this->SQLite->getConnection(), 'test double') extends Table {
            public function exec(string $sql): bool
            {
                throw new SyntaxErrorException('', new RuntimeException());
            }
        };

        $this->expectException(FeatureNotSupportedException::class);
        $testDouble->deleteColumn(Column::createDefaultColumn('test'));
    }

    #[Test]
    public function it_should_truncate_table(): void
    {
        $this->expectException(TableDoesNotExistException::class);
        self::assertFalse($this->SQLite->table->truncate());
    }

    private function getCustomColumnFactory(): ColumnFactory\ColumnFactoryInterface
    {
        return new class implements ColumnFactory\ColumnFactoryInterface {
            public function createColumn(string $name, ?ColumnType $type = null): Column
            {
                return Column::createTextColumn($name, 'foobar');
            }
        };
    }
}
