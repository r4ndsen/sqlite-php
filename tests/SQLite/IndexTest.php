<?php

declare(strict_types=1);

namespace r4ndsen\SQLite;

use BadMethodCallException;
use PHPUnit\Framework\Attributes\Test;
use r4ndsen\SQLite\Exception\UniqueConstraintException;

final class IndexTest extends TestCase
{
    #[Test]
    public function it_should_allow_percent_signs_in_conditions(): void
    {
        $this->SQLite->exec('create table a (name)');

        $index = (new Index(Column::createTextColumn('name')))->setWhere("name like 'a%'");

        self::assertTrue($this->SQLite->a->addIndex($index));
    }

    #[Test]
    public function it_should_create_indexes_on_the_same_column_of_different_tables(): void
    {
        $this->SQLite->exec('create table a (id)');
        $this->SQLite->exec('create table b (id)');

        self::assertTrue($this->SQLite->a->addIndex(new Index(Column::createIntegerColumn('id'))));
        self::assertTrue($this->SQLite->b->addIndex(new Index(Column::createIntegerColumn('id'))));

        self::assertSame(
            ['a_id' => 'a', 'b_id' => 'b'],
            $this->SQLite->fetchPairs("select name, tbl_name from sqlite_master where type = 'index' order by name")
        );
    }
    #[Test]
    public function it_should_create_unique_constraint(): void
    {
        $idx = new Index(Column::createDefaultColumn('id'));
        $idx->setUnique();

        $table = $this->SQLite->table->getDynamicInsertTable();

        $table->push(['id' => 1]);
        $table->addIndex($idx);

        $this->expectException(UniqueConstraintException::class);
        $table->push(['id' => 1]);
    }

    #[Test]
    public function it_should_fail_when_no_columns_are_added_for_index(): void
    {
        $this->expectException(BadMethodCallException::class);

        $index = new Index();
        $index->getCreateStatement('table');
    }

    #[Test]
    public function it_should_index_add_indexed_column(): void
    {
        $this->expectException(BadMethodCallException::class);

        $index = new Index();
        $index->addIndexedColumn(Column::createDefaultColumn('id'));
        self::assertSame('create index if not exists `table_id` on `table` (`id`)', $index->getCreateStatement('table'));

        $index->addIndexedColumn(Column::createDefaultColumn('id2'));
        $index->setUnique();

        $index->getCreateStatement('table');
    }

    #[Test]
    public function it_should_index_set_name(): void
    {
        $index = new Index(Column::createDefaultColumn('id'));
        self::assertSame('create index if not exists `my table_id` on `My Table` (`id`)', $index->getCreateStatement($this->SQLite->getTable('My Table')));

        $index->addIndexedColumn($c = Column::createDefaultColumn('id2'));
        $index->setUnique();

        $index->setName($c);

        $index->setName(' Name 1 2 ');
        $index->setWhere('1=1');

        self::assertSame('create unique index if not exists `name 1 2` on `t` (`id`, `id2`) where 1=1', $index->getCreateStatement('t'));
    }
}
