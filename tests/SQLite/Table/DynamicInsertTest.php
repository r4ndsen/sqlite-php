<?php

declare(strict_types=1);

namespace r4ndsen\SQLite\Table;

use PHPUnit\Framework\Attributes\Test;
use r4ndsen\SQLite;
use r4ndsen\SQLite\Column;
use r4ndsen\SQLite\Exception\ColumnNameAmbiguousException;
use r4ndsen\SQLite\Exception\DeleteColumnException;
use r4ndsen\SQLite\Exception\RenameColumnException;
use r4ndsen\SQLite\Exception\TooManyColumnsException;
use r4ndsen\SQLite\Exception\UniqueConstraintException;
use r4ndsen\SQLite\Table;
use r4ndsen\SQLite\TestCase;
use SQLite3;

final class DynamicInsertTest extends TestCase
{
    #[Test]
    public function it_should_create_multibyte_column_name(): void
    {
        $Table = $this->SQLite->dynamicdata->getDynamicInsertTable();

        self::assertFalse($Table->exists());
        $Table->push(['Ä ' => 'foo']);
        self::assertCount(1, $Table);

        $Table = $this->SQLite->dynamicdata->getDynamicInsertTable();
        self::assertTrue($Table->exists());

        $Table->push([' ä' => 'bar']);
        self::assertCount(2, $Table);
        self::assertSame(
            [
                ['Ä' => 'foo'],
                ['Ä' => 'bar'],
            ],
            $Table->toArray()
        );
    }

    #[Test]
    public function it_should_create_multibyte_column_name_lowercased(): void
    {
        $Table = $this->SQLite->dynamicdata->getDynamicInsertTable();
        self::assertFalse($Table->exists());

        $Table->push(['ä' => 'foo']);

        self::assertTrue($Table->exists());
        self::assertCount(1, $Table);
    }

    #[Test]
    public function it_should_create_multibyte_column_name_uppercased(): void
    {
        $Table = $this->SQLite->dynamicdata->getDynamicInsertTable();
        self::assertFalse($Table->exists());

        $Table->push(['Ä ' => 'foo']);
        $Table->push([' ä' => 'bar']);

        self::assertTrue($Table->exists());
        self::assertCount(2, $Table);

        self::assertSame(
            [
                ['Ä' => 'foo'],
                ['Ä' => 'bar'],
            ],
            $Table->toArray()
        );
    }

    #[Test]
    public function it_should_create_multibyte_column_name_uppercased_reverse(): void
    {
        $Table = $this->SQLite->dynamicdata->getDynamicInsertTable();
        self::assertFalse($Table->exists());

        $Table->push(['ä' => 'bar']);
        $Table->push([' Ä' => 'foo']);

        self::assertTrue($Table->exists());
        self::assertCount(2, $Table);

        self::assertSame(
            [
                ['ä' => 'bar'],
                ['ä' => 'foo'],
            ],
            $Table->toArray()
        );
    }

    /**
     * this test deals with an existing, malformed table which has a non-trimmed column.
     *
     * inserts must still be possible
     */
    #[Test]
    public function it_should_create_multibyte_column_name_uppercased_with_existing_table(): void
    {
        $this->SQLite->exec('create table dynamicdata (` Ä ` TEXT)');

        $Table = $this->SQLite->dynamicdata->getDynamicInsertTable();
        self::assertTrue($Table->exists());

        $Table->push(['ä ' => 'foo']);
        $Table->push([' ä ' => 'bar']);
        $Table->push(['Ä ' => 'baz']);

        self::assertTrue($Table->exists());
        self::assertCount(3, $Table);

        self::assertSame(
            [
                [' Ä ' => 'foo'],
                [' Ä ' => 'bar'],
                [' Ä ' => 'baz'],
            ],
            $Table->toArray()
        );
    }

    #[Test]
    public function it_should_create_multibyte_column_name_uppercased_with_existing_table_multi_case(): void
    {
        $this->SQLite->exec('create table dynamicdata (` Ää`,`Ää`,`äÄ `)');

        $Table = $this->SQLite->dynamicdata->getDynamicInsertTable();
        self::assertTrue($Table->exists());

        $this->expectException(ColumnNameAmbiguousException::class);
        $this->expectExceptionMessage('column name Ää is ambiguous');
        $Table->push([]);
    }

    #[Test]
    public function it_should_dynamic_insert_table(): void
    {
        self::assertInstanceOf(Table::class, $this->SQLite->table);

        $table = $this->SQLite->table->getDynamicInsertTable();

        $table->push(['a' => 1, 'b' => 2]);
        $table->push(['a' => 1, 'b' => 2, 'c' => 3]);
        $table->push(['a' => 1, 'd' => 4, 'c' => 3]);

        self::assertCount(3, $table);

        self::assertSame([
            ['a' => '1', 'b' => '2', 'c' => '', 'd' => ''],
            ['a' => '1', 'b' => '2', 'c' => '3', 'd' => ''],
            ['a' => '1', 'b' => '', 'c' => '3', 'd' => '4'],
        ], $this->SQLite->table->toArray());

        self::assertSame(['1', '1', '1'], $table->fetchCol('select a from `table`'));
        self::assertSame(['2', '2', ''], $table->fetchCol('select b from `table`'));
        self::assertSame(['', '3', '3'], $table->fetchCol('select c from `table`'));
        self::assertSame(['', '', '4'], $table->fetchCol('select d from `table`'));
    }

    #[Test]
    public function it_should_dynamic_insert_table2000_columns(): void
    {
        $data = [];

        foreach (range('a', 'z') as $letter) {
            foreach (range(1, 100) as $id) {
                $data[$letter . $id] = $letter . $id;
            }
        }

        self::assertCount(2_600, $data);

        $table = (new SQLite())->testing1k->getDynamicInsertTable();
        $table->push(\array_slice($data, 0, 2_000));

        self::assertCount(2_000, current($table->toArray()));
    }

    #[Test]
    public function it_should_dynamic_insert_table_create_more_than2000_columns(): void
    {
        $data = [];

        foreach (range('a', 'z') as $letter) {
            foreach (range(1, 100) as $id) {
                $data[$letter . $id] = $letter . $id;
            }
        }

        self::assertCount(2_600, $data);

        $table = (new SQLite())->testing2600->getDynamicInsertTable();

        $this->expectException(TooManyColumnsException::class);
        $this->expectExceptionMessage("Table 'testing2600' has too many columns");

        try {
            $table->push($data);
        } catch (TooManyColumnsException $e) {
            self::assertSame('testing2600', $e->getTable());

            throw $e;
        }
    }

    #[Test]
    public function it_should_dynamic_insert_table_from_regular_table(): void
    {
        self::assertInstanceOf(Table::class, $table = $this->SQLite->table);

        self::assertTrue($table->createFromArray(['c', 'd']));
        self::assertTrue($table->exists());
        self::assertCount(0, $table);
        self::assertCount(2, $table->columns());

        $table->pushWithoutKeys(['3', '4']);

        $table = $this->SQLite->table->getDynamicInsertTable();

        self::assertTrue($table->exists());

        $table->push(['a' => 1, 'b' => 2]);
        $table->push(['a' => 1, 'b' => 2, 'c' => 3]);
        $table->push(['a' => 1, 'd' => 4, 'c' => 3]);

        self::assertCount(4, $table->columns());
        self::assertCount(4, $table);

        self::assertSame(['', '1', '1', '1'], $table->fetchCol('select a from `table`'));
        self::assertSame(['', '2', '2', ''], $table->fetchCol('select b from `table`'));
        self::assertSame(['3', '', '3', '3'], $table->fetchCol('select c from `table`'));
        self::assertSame(['4', '', '', '4'], $table->fetchCol('select d from `table`'));

        $table2 = $this->SQLite->table->getDynamicInsertTable();
        $table2->push(['e' => 5]);

        self::assertCount(5, $table2);
        self::assertSame(['', '', '', '', '5'], $table2->fetchCol('select e from `table`'));
    }

    #[Test]
    public function it_should_dynamic_insert_table_more_than2000_columns(): void
    {
        $data = [];

        foreach (range('a', 'z') as $letter) {
            foreach (range(1, 100) as $id) {
                $data[$letter . $id] = $letter . $id;
            }
        }

        self::assertCount(2_600, $data);

        $table = (new SQLite())->testing2001->getDynamicInsertTable();

        $chunk1 = \array_slice($data, 0, 2_000);
        self::assertCount(2_000, $chunk1);

        // creating 2k columns is fine
        $table->push($chunk1);

        $chunk2 = \array_slice($data, 0, 2_001);
        self::assertCount(2_001, $chunk2);

        $this->expectException(TooManyColumnsException::class);
        $this->expectExceptionMessage("Table 'testing2001' has too many columns");

        $table->push($chunk2);
    }

    #[Test]
    public function it_should_dynamic_insert_table_with_drop(): void
    {
        $table = $this->SQLite->data->getDynamicInsertTable();

        $table->push(['id' => 1, 'title' => 'title 1']);
        self::assertSame([['id' => '1', 'title' => 'title 1']], $table->toArray());

        $table->drop();
        $table->push(['color' => 'red']);
        self::assertSame([['color' => 'red']], $table->toArray());
    }

    #[Test]
    public function it_should_dynamic_insert_table_with_rowid(): void
    {
        $table = $this->SQLite->table->getDynamicInsertTable();

        $table->push(['rowid' => 3, 'a' => 1, 'b' => 2]);
        $table->push(['a' => 1, 'b' => 2, 'c' => 3]);
        $table->push(['a' => 1, 'd' => 4, 'c' => 3]);

        self::assertSame([3, 4, 5], $table->fetchCol('select rowid from `table`'));

        $table->push(['rowid' => 1, 'a' => 1, 'b' => 2]);

        self::assertSame([1, 3, 4, 5], $table->fetchCol('select rowid from `table`'));

        $table->push(['rowid' => 10, 'a' => 1, 'b' => 2]);

        self::assertSame([1, 3, 4, 5, 10], $table->fetchCol('select rowid from `table`'));

        $this->expectException(UniqueConstraintException::class);
        $this->expectExceptionMessage('Unable to execute statement: UNIQUE constraint failed: table.rowid');
        $table->push(['rowid' => 1, 'd' => 1, 'c' => 2]);
    }

    #[Test]
    public function it_should_dynamic_insert_table_with_rowid_reverse_order(): void
    {
        $table = $this->SQLite->table->getDynamicInsertTable();

        $table->push(['a' => 1, 'b' => 2, 'c' => 3]);
        $table->push(['a' => 1, 'd' => 4, 'c' => 3]);
        $table->push(['rowid' => 4, 'a' => 1, 'b' => 2]);

        self::assertSame([1, 2, 4], $table->fetchCol('select rowid from `table`'));

        $table->push(['rowid' => 3, 'a' => 1, 'b' => 2]);

        self::assertSame([1, 2, 3, 4], $table->fetchCol('select rowid from `table`'));

        $table->push(['rowid' => 10, 'a' => 1, 'b' => 2]);

        self::assertSame([1, 2, 3, 4, 10], $table->fetchCol('select rowid from `table`'));

        $this->expectException(UniqueConstraintException::class);
        $this->expectExceptionMessage('Unable to execute statement: UNIQUE constraint failed: table.rowid');
        $table->push(['rowid' => 1, 'd' => 1, 'c' => 2]);
    }

    #[Test]
    public function it_should_dynamic_insert_with_changing_column_case(): void
    {
        $table = $this->SQLite->table->getDynamicInsertTable();
        $table->push(['a' => 1, 'b' => 2]);

        if (SQLite3::version()['versionNumber'] < 3_035_000) {
            $this->expectException(SQLite\Exception\FeatureNotSupportedException::class);
        }

        $table->deleteColumn(Column::createDefaultColumn('a'));
        $table->push(['A' => 2]);

        self::assertSame([
            ['b' => '2', 'A' => ''],
            ['b' => '', 'A' => '2'],
        ], $table->toArray());
    }

    #[Test]
    public function it_should_dynamic_insert_with_changing_column_case_with_index(): void
    {
        $table = $this->SQLite->table->getDynamicInsertTable();
        $col = Column::createDefaultColumn('a');
        $table->push(['a' => 1, 'b' => 2]);
        $table->addIndex(new SQLite\Index($col));

        if (SQLite3::version()['versionNumber'] < 3_035_000) {
            $this->expectException(SQLite\Exception\FeatureNotSupportedException::class);
        } else {
            $this->expectException(DeleteColumnException::class);
        }
        $table->deleteColumn($col);
    }

    #[Test]
    public function it_should_dynamic_insert_with_rename(): void
    {
        $table = $this->SQLite->table->getDynamicInsertTable();
        $colFrom = Column::createDefaultColumn('a');
        $colTo = Column::createDefaultColumn('b');
        $table->push(['a' => 1]);
        $table->renameColumn($colFrom, $colTo);
        $table->push(['b' => 2]);

        self::assertSame([['b' => '1'], ['b' => '2']], $table->toArray());
    }

    #[Test]
    public function it_should_dynamic_insert_with_rename_existing_column(): void
    {
        $table = $this->SQLite->table->getDynamicInsertTable();
        $colFrom = Column::createDefaultColumn('a');
        $colTo = Column::createDefaultColumn('b');
        $table->push(['a' => 1, 'b' => 2]);

        $this->expectException(RenameColumnException::class);
        $table->renameColumn($colFrom, $colTo);
    }

    #[Test]
    public function it_should_fixed_insert_rename_with_index(): void
    {
        $table = $this->SQLite->getTable('table')->getFixedInsertTable();
        $colFrom = Column::createDefaultColumn('foo');
        $colTo = Column::createDefaultColumn('bar');
        $table->push([$colFrom->getLower() => 1]);
        $table->addIndex(new SQLite\Index($colFrom));

        $table->renameColumn($colFrom, $colTo);

        self::assertSame(
            [[$colTo->getLower() => '1']],
            $table->toArray()
        );
    }

    #[Test]
    public function it_should_fixed_insert_with_changing_column_case(): void
    {
        $table = $this->SQLite->getTable('table')->getFixedInsertTable();
        $col = Column::createDefaultColumn('a');
        $table->push(['a' => 1, 'b' => 2]);

        if (SQLite3::version()['versionNumber'] < 3_035_000) {
            $this->expectException(SQLite\Exception\FeatureNotSupportedException::class);
        }

        $table->deleteColumn($col);

        $table->push(['b' => 3]);

        self::assertSame([
            ['b' => '2'],
            ['b' => '3'],
        ], $table->toArray());
    }

    #[Test]
    public function it_should_fixed_insert_with_changing_column_case_with_index(): void
    {
        $table = $this->SQLite->getTable('table')->getFixedInsertTable();
        $col = Column::createDefaultColumn('a');
        $table->push(['a' => 1, 'b' => 2]);
        $table->addIndex(new SQLite\Index($col));

        if (SQLite3::version()['versionNumber'] < 3_035_000) {
            $this->expectException(SQLite\Exception\FeatureNotSupportedException::class);
        } else {
            $this->expectException(DeleteColumnException::class);
        }

        $table->deleteColumn($col);
    }

    #[Test]
    public function it_should_insert_with_numeric_column_names(): void
    {
        $table = $this->SQLite->table->getDynamicInsertTable();

        $table->push(['a', 'b' => 'b']);

        self::assertSame(['0' => 'a', 'b' => 'b'], $table->fetchOne('select * from `table`'));
    }

    #[Test]
    public function it_should_inserts_should_commit_after1000_rows(): void
    {
        $table = $this->SQLite->table->getDynamicInsertTable();

        for ($i = 1; $i <= 1_200; ++$i) {
            $table->push(['id' => $i]);
        }

        self::assertCount(1_200, $table);
    }

    #[Test]
    public function it_should_throw_with_column_name_ambiguous_exception(): void
    {
        $this->SQLite->exec('create table dynamicdata (`Ää`,`äÄ `)');

        $Table = $this->SQLite->dynamicdata->getDynamicInsertTable();
        self::assertTrue($Table->exists());

        $this->expectException(ColumnNameAmbiguousException::class);
        $this->expectExceptionMessage('column name äÄ is ambiguous');
        $Table->push([]);
    }

    #[Test]
    public function it_should_use_untrimmed_column(): void
    {
        $this->SQLite->exec('create table dynamicdata (` foo`,` bar `)');

        $table = $this->SQLite->dynamicdata->getDynamicInsertTable();

        $table->push(['foo' => 1, 'bar' => 2]);

        self::assertCount(1, $table);
    }
}
