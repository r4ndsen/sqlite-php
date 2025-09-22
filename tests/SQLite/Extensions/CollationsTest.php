<?php

declare(strict_types=1);

namespace r4ndsen\SQLite\Extensions;

use PHPUnit\Framework\Attributes\Test;
use r4ndsen\SQLite\Exception\CollationDoesNotExistException;
use r4ndsen\SQLite\Exception\InvalidCollationException;
use r4ndsen\SQLite\Extensions\Collations\CollationInterface;
use r4ndsen\SQLite\TestCase;

final class CollationsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->SQLite->data->getDynamicInsertTable()
            ->push(['id' => 'img2.png'])
            ->push(['id' => 'img12.png'])
            ->push(['id' => 'img10.png'])
            ->push(['id' => 'img1.png'])
            ->commit()
        ;

        $this->SQLite->dataNumerical->getDynamicInsertTable()
            ->push(['id' => '700'])
            ->push(['id' => '800'])
            ->push(['id' => '-6000'])
            ->push(['id' => '-9800'])
            ->push(['id' => '-9100'])
            ->push(['id' => 'img10.png'])
            ->push(['id' => 'img8.png'])
            ->commit()
        ;

        $this->SQLite->exec('CREATE TABLE data2 (`id` text); INSERT INTO data2 values (1), (null), (0), (-1), (10), (2);');
        $this->SQLite->exec('CREATE TABLE data3 (`id` integer); INSERT INTO data3 values (1), (null), (0), (-1), (10), (2);');
    }

    #[Test]
    public function it_should_collation_does_not_exist(): void
    {
        $this->expectException(CollationDoesNotExistException::class);
        $this->SQLite->exec('select * from data order by id COLLATE FOO');
    }

    #[Test]
    public function it_should_collation_does_not_exist2(): void
    {
        $this->expectException(CollationDoesNotExistException::class);
        $this->SQLite->exec('select * from data order by id COLLATE FOO');
    }

    #[Test]
    public function it_should_natural_compare_with_negative_values(): void
    {
        self::assertSame([
            0 => '-6000',
            1 => '-9100',
            2 => '-9800',
            3 => '700',
            4 => '800',
            5 => 'img8.png',
            6 => 'img10.png',
        ], $this->SQLite->fetchCol('select id from dataNumerical order by id COLLATE NATURAL_CMP'));

        self::assertSame([
            0 => 'img10.png',
            1 => 'img8.png',
            2 => '800',
            3 => '700',
            4 => '-9800',
            5 => '-9100',
            6 => '-6000',
        ], $this->SQLite->fetchCol('select id from dataNumerical order by id COLLATE NATURAL_CMP desc'));
    }

    #[Test]
    public function it_should_order_by_natural_compare(): void
    {
        self::assertSame([
            0 => 'img1.png',
            1 => 'img10.png',
            2 => 'img12.png',
            3 => 'img2.png',
        ], $this->SQLite->fetchCol('select id from data order by id'));

        self::assertSame([
            0 => 'img1.png',
            1 => 'img2.png',
            2 => 'img10.png',
            3 => 'img12.png',
        ], $this->SQLite->fetchCol('select id from data order by id COLLATE NATURAL_CMP'));

        self::assertSame([
            0 => 'img12.png',
            1 => 'img10.png',
            2 => 'img2.png',
            3 => 'img1.png',
        ], $this->SQLite->fetchCol('select id from data order by id COLLATE NATURAL_CMP desc'));
    }

    #[Test]
    public function it_should_order_by_numerical_compare(): void
    {
        self::assertSame([
            0 => '-6000',
            1 => '-9100',
            2 => '-9800',
            3 => '700',
            4 => '800',
            5 => 'img10.png',
            6 => 'img8.png',
        ], $this->SQLite->fetchCol('select id from dataNumerical order by id'));

        self::assertSame([
            0 => '-9800',
            1 => '-9100',
            2 => '-6000',
            3 => '700',
            4 => '800',
            5 => 'img10.png',
            6 => 'img8.png',
        ], $this->SQLite->fetchCol('select id from dataNumerical order by id COLLATE NUMERICAL_CMP'));

        self::assertSame([
            0 => 'img8.png',
            1 => 'img10.png',
            2 => '800',
            3 => '700',
            4 => '-6000',
            5 => '-9100',
            6 => '-9800',
        ], $this->SQLite->fetchCol('select id from dataNumerical order by id COLLATE NUMERICAL_CMP desc'));
    }

    #[Test]
    public function it_should_reject_blank_collation_identifier(): void
    {
        $collations = new Collations($this->SQLite->getConnection());

        $identifier = str_repeat(' ', 2);

        $failingCollation = new class($identifier) implements CollationInterface {
            public function __construct(private readonly string $identifier)
            {
            }

            public function getCallback(): callable
            {
                return static fn ($left, $right) => 0;
            }

            public function getIdentifier(): string
            {
                return $this->identifier;
            }
        };

        $this->expectException(InvalidCollationException::class);
        $this->expectExceptionMessage('Failed to create collation: identifier must not be empty');

        $collations->add($failingCollation);
    }

    #[Test]
    public function it_should_sort_order_on_strings_and_ints_with_native_sorting(): void
    {
        self::assertNotSame(
            array_map('strval', $this->SQLite->fetchCol('select id from data2 order by id')),
            array_map('strval', $this->SQLite->fetchCol('select id from data3 order by id'))
        );

        self::assertNotSame(
            array_map('intval', $this->SQLite->fetchCol('select id from data2 order by id')),
            array_map('intval', $this->SQLite->fetchCol('select id from data3 order by id'))
        );
    }

    #[Test]
    public function it_should_sort_order_on_strings_and_ints_with_natural_sorting(): void
    {
        self::assertSame(
            array_map('strval', $this->SQLite->fetchCol('select id from data2 order by id COLLATE NATURAL_CMP')),
            array_map('strval', $this->SQLite->fetchCol('select id from data3 order by id COLLATE NATURAL_CMP'))
        );

        self::assertSame(
            array_map('intval', $this->SQLite->fetchCol('select id from data2 order by id COLLATE NATURAL_CMP')),
            array_map('intval', $this->SQLite->fetchCol('select id from data3 order by id COLLATE NATURAL_CMP'))
        );
    }

    #[Test]
    public function it_should_sort_order_on_strings_and_ints_with_numerical_sorting(): void
    {
        self::assertSame(
            array_map('strval', $this->SQLite->fetchCol('select id from data2 order by id COLLATE NUMERICAL_CMP')),
            array_map('strval', $this->SQLite->fetchCol('select id from data3 order by id COLLATE NUMERICAL_CMP'))
        );

        self::assertSame(
            array_map('intval', $this->SQLite->fetchCol('select id from data2 order by id COLLATE NUMERICAL_CMP')),
            array_map('intval', $this->SQLite->fetchCol('select id from data3 order by id COLLATE NUMERICAL_CMP'))
        );
    }

    #[Test]
    public function it_should_sort_with_null_values(): void
    {
        self::assertSame([
            null,
            '-1',
            '0',
            '1',
            '10',
            '2',
        ], $this->SQLite->fetchCol('select id from data2 order by id'));

        self::assertSame([
            null,
            '-1',
            '0',
            '1',
            '2',
            '10',
        ], $this->SQLite->fetchCol('select id from data2 order by id COLLATE NATURAL_CMP'));

        self::assertSame([
            null,
            '-1',
            '0',
            '1',
            '2',
            '10',
        ], $this->SQLite->fetchCol('select id from data2 order by id COLLATE NUMERICAL_CMP'));

        self::assertNotSame(
            $this->SQLite->fetchCol('select id from data2 order by id'),
            $this->SQLite->fetchCol('select id from data2 order by id COLLATE NATURAL_CMP')
        );
    }

    #[Test]
    public function it_should_throw_invalid_collation_when_creation_fails(): void
    {
        $collations = new class($this->SQLite->getConnection()) extends Collations {
            protected function registerCollation(string $identifier, callable $callback): bool
            {
                return false;
            }
        };

        $failingCollation = new class implements CollationInterface {
            public function getCallback(): callable
            {
                return static fn ($left, $right) => 0;
            }

            public function getIdentifier(): string
            {
                return 'failing_collation';
            }
        };

        $this->expectException(InvalidCollationException::class);
        $this->expectExceptionMessage('Failed to create collation: failing_collation');

        $collations->add($failingCollation);
    }
}
