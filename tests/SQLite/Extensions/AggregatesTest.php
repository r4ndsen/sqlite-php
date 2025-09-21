<?php

declare(strict_types=1);

namespace r4ndsen\SQLite\Extensions;

use PHPUnit\Framework\Attributes\Test;
use r4ndsen\SQLite\ColumnFactory\ColumnFactory;
use r4ndsen\SQLite\Exception\InvalidAggregateException;
use r4ndsen\SQLite\Extensions\Aggregates\AggregateInterface;
use r4ndsen\SQLite\Extensions\Aggregates\GroupConcatUnique;
use r4ndsen\SQLite\TestCase;

final class AggregatesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->SQLite->test
            ->getDynamicInsertTable()
            ->setColumnFactory(new ColumnFactory(null))
            ->push(['id' => '1', 'color' => 'red', 'size' => 'M'])
            ->push(['id' => '1', 'color' => 'green', 'size' => 'M'])
            ->push(['id' => '2', 'color' => 'green', 'size' => 'M'])
            ->push(['id' => '1', 'color' => 'blue', 'size' => 'S', 'nil' => 'nil'])
            ->commit()
        ;
    }

    #[Test]
    public function it_should_first_and_last_color_row(): void
    {
        self::assertSame(2, $this->SQLite->fetchValue("select first(rowid) from `test` where color = 'green' group by color"));
        self::assertSame(3, $this->SQLite->fetchValue("select last(rowid)  from `test` where color = 'green' group by color"));
    }

    #[Test]
    public function it_should_first_and_last_color_row_without_grouping(): void
    {
        self::assertSame(2, $this->SQLite->fetchValue("select first(rowid) from `test` where color = 'green'"));
        self::assertSame(3, $this->SQLite->fetchValue("select last(rowid)  from `test` where color = 'green'"));
    }

    #[Test]
    public function it_should_first_and_last_nil(): void
    {
        self::assertNull($this->SQLite->fetchValue('select first(nil) from `test`'));
        self::assertSame('nil', $this->SQLite->fetchValue('select first(nil) from `test` where nil is not null'));
        self::assertSame('nil', $this->SQLite->fetchValue('select last(nil) from `test`'));
        self::assertNull($this->SQLite->fetchValue('select first(nil)  from `test` where false'));
        self::assertNull($this->SQLite->fetchValue('select last(nil)  from `test` where false'));
    }

    #[Test]
    public function it_should_first_and_last_size_row(): void
    {
        self::assertSame([1 => 'M', 4 => 'S'], $this->SQLite->fetchPairs('select first(rowid), size from `test` group by size'));
        self::assertSame([3 => 'M', 4 => 'S'], $this->SQLite->fetchPairs('select last(rowid),  size from `test` group by size'));
        self::assertSame('M', $this->SQLite->fetchValue('select first(size) from `test`'));
        self::assertSame('S', $this->SQLite->fetchValue('select last(size) from `test`'));
    }

    #[Test]
    public function it_should_group_concat(): void
    {
        self::assertSame(
            [
                'M' => 'red | green | green',
                'S' => 'blue',
            ],
            $this->SQLite->fetchPairs('select `size`, groupConcat(`color`, " | ") as `color` from `test` group by `size`')
        );
    }

    #[Test]
    public function it_should_group_concat_unique(): void
    {
        self::assertSame([
            'M' => 'red | green',
            'S' => 'blue',
        ], $this->SQLite->fetchPairs('select `size`, groupConcatUnique(`color`, " | ") as `color` from `test` group by `size`'));
    }

    #[Test]
    public function it_should_keep_first_row_by_id(): void
    {
        self::assertSame([1 => '1', 3 => '2'], $this->SQLite->fetchPairs('select first(rowid), id from `test` group by id'));
        self::assertSame([3 => '2', 1 => '1'], $this->SQLite->fetchPairs('select first(rowid), id from `test` group by id order by id desc'));
        self::assertSame([3 => '2', 1 => '1'], $this->SQLite->fetchPairs('select first(rowid), id from `test` group by id order by rowid desc'));
    }

    #[Test]
    public function it_should_keep_last_row_by_id(): void
    {
        self::assertSame([1 => '1', 3 => '2'], $this->SQLite->fetchPairs('select first(rowid), id from `test` group by id order by id asc'));
        self::assertSame([3 => '2', 1 => '1'], $this->SQLite->fetchPairs('select first(rowid), id from `test` group by id order by id desc'));
        self::assertSame([4 => '1', 3 => '2'], $this->SQLite->fetchPairs('select last(rowid), id  from `test` group by id order by id asc'));
        self::assertSame([3 => '2', 4 => '1'], $this->SQLite->fetchPairs('select last(rowid), id  from `test` group by id order by id desc'));
    }

    #[Test]
    public function it_should_reject_blank_aggregate_identifier(): void
    {
        $aggregates = new Aggregates($this->SQLite->getConnection());

        $identifier = str_repeat(' ', 3);

        $failingAggregate = new class($identifier) implements AggregateInterface {
            public function __construct(private readonly string $identifier)
            {
            }

            public function getCallback(): callable
            {
                return static fn () => null;
            }

            public function getFinalCallback(): callable
            {
                return static fn () => null;
            }

            public function getIdentifier(): string
            {
                return $this->identifier;
            }
        };

        $this->expectException(InvalidAggregateException::class);
        $this->expectExceptionMessage('Failed to create aggregate: identifier must not be empty');

        $aggregates->add($failingAggregate);
    }

    #[Test]
    public function it_should_store_unique_group_concat_entries_as_booleans(): void
    {
        $aggregate = new GroupConcatUnique();
        $callback = $aggregate->getCallback();

        $context = $callback(null, 1, 'alpha');
        $context = $callback($context, 2, 'alpha');

        self::assertTrue($context['data']['alpha']);

        $final = $aggregate->getFinalCallback();
        self::assertSame('alpha', $final($context));
    }

    #[Test]
    public function it_should_throw_invalid_aggregate_when_creation_fails(): void
    {
        $aggregates = new class($this->SQLite->getConnection()) extends Aggregates {
            protected function registerAggregate(string $identifier, callable $callback, callable $finalCallback): bool
            {
                return false;
            }
        };

        $failingAggregate = new class implements AggregateInterface {
            public function getCallback(): callable
            {
                return static fn () => null;
            }

            public function getFinalCallback(): callable
            {
                return static fn () => null;
            }

            public function getIdentifier(): string
            {
                return 'failing_aggregate';
            }
        };

        $this->expectException(InvalidAggregateException::class);
        $this->expectExceptionMessage('Failed to create aggregate: failing_aggregate');

        $aggregates->add($failingAggregate);
    }
}
