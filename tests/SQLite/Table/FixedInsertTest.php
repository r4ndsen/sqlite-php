<?php

declare(strict_types=1);

namespace r4ndsen\SQLite\Table;

use PHPUnit\Framework\Attributes\Test;
use r4ndsen\SQLite\Exception\ColumnDoesNotExistException;
use r4ndsen\SQLite\Table;
use r4ndsen\SQLite\TestCase;

final class FixedInsertTest extends TestCase
{
    #[Test]
    public function it_should_fixed_insert_table(): void
    {
        $table = $this->SQLite->getTable('dynamicdata')->getFixedInsertTable();
        self::assertFalse($table->exists());

        $table->push(['Ä ' => 'foo']);
        $table->push(['rowid' => 2, ' ä' => 'bar']);
        $table->push(['ä' => 'baz']); // auto increment to 3
        $table->push(['rowid' => 99, ' ä' => 'voo']);
        $table->push(['Ä' => 'doo']); // auto increment to 100

        self::assertTrue($table->exists());
        self::assertCount(5, $table);

        self::assertSame(
            [
                '1'   => 'foo',
                '2'   => 'bar',
                '3'   => 'baz',
                '99'  => 'voo',
                '100' => 'doo',
            ],
            $table->fetchPairs("select rowid, * from {$table}")
        );
    }

    #[Test]
    public function it_should_fixed_insert_table_with_new_keys(): void
    {
        /** @var Table */
        $table = $this->SQLite->getTable('dynamicdata')->getFixedInsertTable();
        self::assertFalse($table->exists());

        $table->push(['foo' => '1']);
        $table->push(['rowid' => 321, 'foo' => '2']);
        self::assertTrue($table->exists());
        self::assertCount(2, $table);
        self::assertSame(321, $table->maxRow());

        $this->expectException(ColumnDoesNotExistException::class);
        $table->push(['bar' => '1']);
    }
}
