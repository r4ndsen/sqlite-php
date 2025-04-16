<?php

declare(strict_types=0);

namespace r4ndsen\SQLite;

use BadFunctionCallException;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use r4ndsen\SQLite;
use Stringable;

final class ColumnTest extends TestCase
{
    #[Test]
    public function it_should_column_types(): void
    {
        $sqlite = new SQLite();
        $sqlite->data->addCreateColumn(Column::createBlobColumn('Test 1'));
        $sqlite->data->addCreateColumn(Column::createIntegerColumn('Test 2'));
        $sqlite->data->addCreateColumn(Column::createFloatColumn('Test 3'));
        $sqlite->data->addCreateColumn(Column::createNumericColumn('Test 4'));
        $sqlite->data->create();

        $expect = [
            'Test 1' => 'test',
            'Test 2' => 123,
            'Test 3' => 123.456,
            'Test 4' => 123,
        ];

        $sqlite->data->push($expect);

        self::assertSame($expect, $sqlite->data->fetchOne('select * from data'));

        self::assertSame(ColumnType::BLOB, $sqlite->data->getColumnByName('Test 1')->getType());
        self::assertSame(ColumnType::INTEGER, $sqlite->data->getColumnByName('Test 2')->getType());
        self::assertSame(ColumnType::REAL, $sqlite->data->getColumnByName('Test 3')->getType());
        self::assertSame(ColumnType::NUMERIC, $sqlite->data->getColumnByName('Test 4')->getType());
    }

    #[Test]
    public function it_should_create_blob_column(): void
    {
        $C = Column::createBlobColumn('blob');

        self::assertNull($C->getDefaultValue());
        self::assertSame(ColumnType::BLOB, $C->getType());
    }

    #[Test]
    public function it_should_create_float_column(): void
    {
        $C = Column::createFloatColumn('float', null);

        self::assertNull($C->getDefaultValue());
        self::assertSame(ColumnType::REAL, $C->getType());
    }

    #[Test]
    public function it_should_create_integer_column(): void
    {
        $C = Column::createIntegerColumn('int');

        self::assertNull($C->getDefaultValue());
        self::assertSame(ColumnType::INTEGER, $C->getType());
    }

    #[Test]
    public function it_should_create_text_column_with_float_default_value(): void
    {
        $a = Column::createTextColumn('testA', (string) M_PI);
        self::assertSame((string) M_PI, $a->getDefaultValue());

        $table = $this->SQLite->test;
        $table->addCreateColumn($a);
        self::assertTrue($table->create());

        $table->getDynamicInsertTable()->push(['rowid' => 10]);
        self::assertSame((string) M_PI, $this->SQLite->fetchValue('select * from test'));
    }

    #[Test]
    public function it_should_create_text_column_with_integer_default_value(): void
    {
        $a = Column::createTextColumn('testA', (string) PHP_INT_MAX);
        self::assertSame((string) PHP_INT_MAX, $a->getDefaultValue());

        $table = $this->SQLite->test;
        $table->addCreateColumn($a);
        self::assertTrue($table->create());

        $table->getDynamicInsertTable()->push(['rowid' => 10]);
        self::assertSame((string) PHP_INT_MAX, $this->SQLite->fetchValue('select * from test'));
    }

    #[Test]
    public function it_should_create_text_column_with_stringable_default_value(): void
    {
        $a = Column::createTextColumn('testA', new StringableClass('A'));
        $b = Column::createTextColumn('testB', new StringableClass('B'));
        self::assertSame('A', $a->getDefaultValue());

        $table = $this->SQLite->test;
        $table->addCreateColumn($a);
        $table->addCreateColumn($b);
        self::assertTrue($table->create());

        $table->getDynamicInsertTable()->push(['rowid' => 10]);
        self::assertSame(['A' => 'B'], $this->SQLite->fetchPair('select testA, testB from test'));

        $table->getDynamicInsertTable()->push(['rowid' => 9, 'testB' => 'value']);
        self::assertSame(['A' => 'value'], $this->SQLite->fetchPair('select testA, testB from test'));

        $table->getDynamicInsertTable()->push(['rowid' => 8, 'testA' => 'value']);
        self::assertSame(['value' => 'B'], $this->SQLite->fetchPair('select testA, testB from test'));

        $table->getDynamicInsertTable()->push(['rowid' => 7, 'testA' => 'value', 'testB' => 'value']);
        self::assertSame(['value' => 'value'], $this->SQLite->fetchPair('select testA, testB from test'));
    }

    #[Test]
    public function it_should_debug_info(): void
    {
        $C = Column::createDefaultColumn('Test 1');
        self::assertSame("`Test 1` TEXT default ''", $C->getCreateStatement());

        $result = $C->__debugInfo();

        self::assertNull($result['column id']);
        self::assertSame('', $result['default value']);

        self::assertSame(0, $result['primary key']);
        self::assertSame(0, $result['not null']);
        self::assertSame('TEXT', $result['type']);
        self::assertSame('Test 1', $result['name']);
    }

    #[Test]
    public function it_should_disallow_null(): void
    {
        $this->expectException(BadFunctionCallException::class);

        $C = Column::createTextColumn('test', null);
        $C->disallowNull();
    }

    #[Test]
    public function it_should_get_create_statement(): void
    {
        $C = Column::createDefaultColumn('Test 1');
        self::assertSame("`Test 1` TEXT default ''", $C->getCreateStatement());

        $C = Column::createDefaultColumn('Test 1');
        $C->disallowNull();

        self::assertSame("`Test 1` TEXT default '' NOT NULL", $C->getCreateStatement());
        $C->allowNull();
        self::assertSame("`Test 1` TEXT default ''", $C->getCreateStatement());
    }

    #[Test]
    public function it_should_get_escaped(): void
    {
        $C = Column::createDefaultColumn('TeSt Ö 123 ');
        self::assertSame('`TeSt Ö 123 `', $C->getEscaped());
        self::assertSame('`test ö 123`', $C->getLowerTrimmedEscaped());
        self::assertSame('test ö 123', $C->getTrimmedLower());

        $C = Column::createDefaultColumn('TeSt` 123');
        self::assertSame('`TeSt`` 123`', (string) $C);

        $C = Column::createDefaultColumn("some ' thing");
        self::assertSame("`some ' thing`", (string) $C);
    }

    #[Test]
    public function it_should_get_lower(): void
    {
        $C = Column::createDefaultColumn('TeSt Ö 123 ');

        self::assertSame('test ö 123 ', $C->getLower());
    }

    #[Test]
    public function it_should_get_plain(): void
    {
        $C = Column::createDefaultColumn('TeSt 123');

        self::assertSame('TeSt 123', $C->getPlain());
    }

    #[Test]
    public function it_should_methods(): void
    {
        $C = Column::createDefaultColumn('test');
        self::assertSame(ColumnType::TEXT, $C->getType());
        self::assertSame('', $C->getDefaultValue());
        self::assertNull($C->getColumnId());
        self::assertNull($C->getPk());
        self::assertSame('`test`', (string) $C);
        self::assertSame('test', $C->getLower());
        self::assertSame('test', $C->getPlain());
    }

    #[Test]
    public function it_should_throw_on_invalid_schema(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Column::createFromSchema([]);
    }
}

class StringableClass implements Stringable
{
    public function __construct(private readonly string $value)
    {
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
