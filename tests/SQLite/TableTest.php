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
    // database file of a test, removed after the test
    private ?string $tempdb = null;

    protected function tearDown(): void
    {
        if ($this->tempdb !== null) {
            // close the connection before deleting the file
            $this->SQLite = new SQLite();
            gc_collect_cycles();
            file_exists($this->tempdb) && unlink($this->tempdb);
        }
    }

    #[Test]
    public function delete_column_on_non_existing_table_via_perform(): void
    {
        if (SQLite3::version()['versionNumber'] < 3_035_000) {
            $this->expectException(SyntaxErrorException::class);
        } else {
            $this->expectException(TableDoesNotExistException::class);
        }

        $this->SQLite->exec('alter table `foo` drop column `bar`');
    }

    #[Test]
    public function it_should_add_column(): void
    {
        $tempdb = $this->tempdb = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('table-', true) . '.sqlite';
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
        self::assertSame('unique (`test`)', $constraint->getCreateStatement());

        $constraint->primaryKey();
        self::assertSame('primary key (`test`)', $constraint->getCreateStatement());

        $t->addCreateColumn($column);
        $t->addConstraint($constraint);

        $t->create();
    }

    #[Test]
    public function it_should_add_constraint_add_index2(): void
    {
        $c = Column::createDefaultColumn('test');

        $index = new Index();
        $index
            ->addIndexedColumn($c)
            ->setUnique()
            ->setWhere('1=2')
        ;

        $this->SQLite->table
            ->addCreateColumn($c)
            ->create()
        ;

        self::assertTrue($this->SQLite->table->addIndex($index));
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
        self::assertSame('unique (`test`)', $constraint->getCreateStatement());

        $constraint->onConflict = OnConflict::IGNORE;

        $constraint->setName(' my constraint ');
        self::assertSame('constraint `my constraint` unique (`test`) on conflict ignore', $constraint->getCreateStatement());

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
        self::assertFalse($c->getIsPrimaryKey());

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
    public function it_should_allow_subclasses_to_use_the_protected_methods(): void
    {
        $table = new class($this->SQLite->getConnection(), 't') extends Table {
            public function createStatement(): string
            {
                return $this->buildCreateStatement();
            }

            public function rows(): bool
            {
                return $this->hasRows();
            }
        };

        $table->addCreateColumn(Column::createDefaultColumn('a'));
        self::assertSame("`a` TEXT default ''", $table->createStatement());

        $table->create();
        self::assertFalse($table->rows());
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
    public function it_should_count_rows_of_tables_without_rowid(): void
    {
        $this->SQLite->exec('create table t (a primary key) without rowid');
        $this->SQLite->exec("insert into t values ('x')");

        self::assertCount(1, $this->SQLite->t);
        self::assertSame(0, $this->SQLite->t->maxRow());
    }

    #[Test]
    public function it_should_count_rows_with_rowid_zero(): void
    {
        $this->SQLite->exec('create table t (a)');
        $this->SQLite->exec("insert into t (rowid, a) values (0, 'x')");

        self::assertCount(1, $this->SQLite->t);
        self::assertTrue($this->SQLite->t->hasData());
    }

    #[Test]
    public function it_should_create_allow_transaction(): void
    {
        $t = $this->SQLite->getTable('table');

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
    public function it_should_create_from_array_with_duplicate_column_names(): void
    {
        self::assertTrue($this->SQLite->t->createFromArray(['a', 'a']));
        self::assertSame(['a'], array_keys($this->SQLite->t->columns()));
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
        self::assertTrue($this->SQLite->getTable('table')->drop());
    }

    #[Test]
    public function it_should_find_multibyte_columns_case_insensitively(): void
    {
        $this->SQLite->exec('create table t (äpfel)');

        self::assertTrue($this->SQLite->t->columnExists('ÄPFEL'));
        self::assertSame('äpfel', $this->SQLite->t->getColumnByName(' ÄPFEL ')?->getRaw());
    }

    #[Test]
    public function it_should_find_tables_case_insensitively(): void
    {
        $this->SQLite->exec('create table Tasks (a)');

        self::assertTrue($this->SQLite->getTable('tasks')->exists());
        self::assertCount(1, $this->SQLite->getTable('TASKS')->columns());
    }

    #[Test]
    public function it_should_have_no_data_when_the_table_is_empty(): void
    {
        $this->SQLite->exec('create table t (a)');

        self::assertFalse($this->SQLite->t->hasData());
    }

    #[Test]
    public function it_should_have_no_schema_for_a_view(): void
    {
        $this->SQLite->exec('create view v as select 1 as a');

        self::assertSame([], $this->SQLite->v->schema());
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
    public function it_should_limit_the_number_of_prepared_statements(): void
    {
        $columns = array_map(static fn (int $i) => 'c' . $i, range(0, 100));
        $table = $this->SQLite->t;
        $table->createFromArray($columns);

        foreach ($columns as $column) {
            $table->push([$column => 1]);
        }

        // the statements were committed and dropped before preparing the 101st
        $preparedStatements = $this->getPropertyValue($table, 'preparedStatements');
        self::assertIsArray($preparedStatements);
        self::assertCount(1, $preparedStatements);
        self::assertCount(101, $table);
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
    public function it_should_not_list_internal_tables(): void
    {
        $this->SQLite->exec('create table t (id integer primary key autoincrement)');
        $this->SQLite->exec('insert into t default values');

        self::assertSame(['t'], array_map(static fn (Table $table) => $table->getName(), $this->SQLite->getAllTables()));
    }

    #[Test]
    public function it_should_push_empty_rows_with_default_values(): void
    {
        $this->SQLite->exec("create table t (a default 'x', b)");
        $this->SQLite->t->push([])->commit();

        self::assertSame([['a' => 'x', 'b' => null]], $this->SQLite->t->toArray());
    }

    #[Test]
    public function it_should_push_rows_by_column_name(): void
    {
        $this->SQLite->exec('create table t (a, b)');
        $t = $this->SQLite->getTable('t');

        $t->push(['a' => 1, 'b' => 2])
            ->push([3, 4])
            ->push(['b' => 5, 'a' => 6])
            ->push([7, 8])
            ->push(['b' => 9])
            ->commit()
        ;

        self::assertSame([
            ['a' => 1, 'b' => 2],
            ['a' => 3, 'b' => 4],
            ['a' => 6, 'b' => 5],
            ['a' => 8, 'b' => 7],
            ['a' => null, 'b' => 9],
        ], $t->toArray());
    }

    #[Test]
    public function it_should_rename_column(): void
    {
        $this->SQLite->exec('create table t (a)');

        self::assertTrue($this->SQLite->t->renameColumn(Column::createDefaultColumn('a'), Column::createDefaultColumn('b')));
        self::assertSame(['b'], array_keys($this->SQLite->t->columns()));
    }

    #[Test]
    public function it_should_table_general_methods(): void
    {
        $tableName = 'table';
        $table = $this->SQLite->getTable($tableName);

        self::assertSame('table', $table->getName());
        self::assertFalse($table->exists());
        self::assertCount(0, $table);
        self::assertSame(0, $table->maxRow());
        self::assertSame([], $table->columns());
        self::assertSame([], $this->invokeArgs($table, 'schema'));
        self::assertNull($table->getColumnByName('test'));
        self::assertNull($table->getColumnByColumnId(0));
        self::assertTrue($table->drop());
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
        $this->expectExceptionMessage('Drop column syntax can be used from SQLite3 3.35.0 - your version is: ' . SQLite3::version()['versionString']);
        $testDouble->deleteColumn(Column::createDefaultColumn('test'));
    }

    #[Test]
    public function it_should_trim_table_name_and_match_multibyte_columns(): void
    {
        $rawName = '  ' . __FUNCTION__ . '  ';
        $table = new Table($this->SQLite->getConnection(), $rawName);
        $table->addCreateColumn(Column::createDefaultColumn('Ä'));

        self::assertTrue($table->create());
        self::assertSame(trim($rawName), $table->getName());
        self::assertSame('`' . trim($rawName) . '`', (string) $table);

        self::assertTrue($table->columnExists('  ä  '));
        self::assertFalse($table->columnExists('ö'));
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
