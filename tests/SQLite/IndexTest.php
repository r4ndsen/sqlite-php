<?php

declare(strict_types=1);

namespace r4ndsen\SQLite;

use BadMethodCallException;
use PHPUnit\Framework\Attributes\Test;
use r4ndsen\SQLite\Exception\UniqueConstraintException;

final class IndexTest extends TestCase
{
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

        $Index = new Index();
        $Index->getCreateStatement();
    }

    #[Test]
    public function it_should_index_add_indexed_column(): void
    {
        $this->expectException(BadMethodCallException::class);

        $Index = new Index();
        $Index->addIndexedColumn(Column::createDefaultColumn('id'));
        self::assertSame('CREATE INDEX if not exists `id` on %s (`id`)', $Index->getCreateStatement());

        $Index->addIndexedColumn(Column::createDefaultColumn('id2'));
        $Index->setUnique();

        self::assertSame('CREATE UNIQUE INDEX if not exists `id` on %s (`id`, `id2`)', $Index->getCreateStatement());
    }

    #[Test]
    public function it_should_index_set_name(): void
    {
        $Index = new Index(Column::createDefaultColumn('id'));
        self::assertSame('CREATE INDEX if not exists `id` on %s (`id`)', $Index->getCreateStatement());

        $Index->addIndexedColumn($C = Column::createDefaultColumn('id2'));
        $Index->setUnique();

        $Index->setName($C);

        $Index->setName(' Name 1 2 ');
        $Index->setWhere('1=1');

        self::assertSame('CREATE UNIQUE INDEX if not exists `name 1 2` on %s (`id`, `id2`) where 1=1', $Index->getCreateStatement());
    }
}
