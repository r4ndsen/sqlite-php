<?php

declare(strict_types=0);

namespace r4ndsen\SQLite;

use BadFunctionCallException;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use r4ndsen\SQLite;
use stdClass;
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
        $c = Column::createBlobColumn('blob');

        self::assertNull($c->getDefaultValue());
        self::assertSame(ColumnType::BLOB, $c->getType());
    }

    #[Test]
    public function it_should_create_float_column(): void
    {
        $c = Column::createFloatColumn('float', null);

        self::assertNull($c->getDefaultValue());
        self::assertSame(ColumnType::REAL, $c->getType());
    }

    #[Test]
    public function it_should_create_integer_column(): void
    {
        $c = Column::createIntegerColumn('int');

        self::assertNull($c->getDefaultValue());
        self::assertSame(ColumnType::INTEGER, $c->getType());
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
        $c = Column::createDefaultColumn('Test 1');
        self::assertSame("`Test 1` TEXT default ''", $c->getCreateStatement());

        $result = $c->__debugInfo();

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

        $c = Column::createTextColumn('test', null);
        $c->disallowNull();
    }

    #[Test]
    public function it_should_get_create_statement(): void
    {
        $c = Column::createDefaultColumn('Test 1');
        self::assertSame("`Test 1` TEXT default ''", $c->getCreateStatement());

        $c = Column::createDefaultColumn('Test 1');
        $c->disallowNull();

        self::assertSame("`Test 1` TEXT default '' NOT NULL", $c->getCreateStatement());
        $c->allowNull();
        self::assertSame("`Test 1` TEXT default ''", $c->getCreateStatement());
    }

    #[Test]
    public function it_should_get_escaped(): void
    {
        $c = Column::createDefaultColumn('TeSt Ö 123 ');
        self::assertSame('`TeSt Ö 123 `', $c->getEscaped());
        self::assertSame('`test ö 123`', $c->getLowerTrimmedEscaped());
        self::assertSame('test ö 123', $c->getTrimmedLower());

        $c = Column::createDefaultColumn('TeSt` 123');
        self::assertSame('`TeSt`` 123`', (string) $c);

        $c = Column::createDefaultColumn("some ' thing");
        self::assertSame("`some ' thing`", (string) $c);
    }

    #[Test]
    public function it_should_get_lower(): void
    {
        $c = Column::createDefaultColumn('TeSt Ö 123 ');

        self::assertSame('test ö 123 ', $c->getLower());
    }

    #[Test]
    public function it_should_get_plain(): void
    {
        $c = Column::createDefaultColumn('TeSt 123');

        self::assertSame('TeSt 123', $c->getPlain());
    }

    #[Test]
    public function it_should_methods(): void
    {
        $c = Column::createDefaultColumn('test');
        self::assertSame(ColumnType::TEXT, $c->getType());
        self::assertSame('', $c->getDefaultValue());
        self::assertNull($c->getColumnId());
        self::assertNull($c->getPk());
        self::assertSame('`test`', (string) $c);
        self::assertSame('test', $c->getLower());
        self::assertSame('test', $c->getPlain());
    }

    #[Test]
    public function it_should_throw_on_invalid_schema(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Column::createFromSchema([]);
    }

    #[Test]
    public function it_should_throw_on_non_scalar_input(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Column('test', defaultValue: new stdClass());
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
