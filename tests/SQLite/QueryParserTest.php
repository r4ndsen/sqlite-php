<?php

declare(strict_types=1);

namespace r4ndsen\SQLite;

use PHPUnit\Framework\Attributes\Test;
use r4ndsen\SQLite\Exception\ColumnDoesNotExistException;

final class QueryParserTest extends TestCase
{
    #[Test]
    public function it_should_double_quoted_identifier(): void
    {
        $parameters = ['foo' => ['bar', 'baz']];
        $sql = <<<SQL
SELECT ":foo"
SQL;
        [$statement, $values] = $this->rebuild($sql, $parameters);
        self::assertSame($sql, $statement);

        $sql = <<<SQL
SELECT "to use double quotes, just double them "" :foo "
SQL;
        [$statement, $values] = $this->rebuild($sql, $parameters);
        self::assertSame($sql, $statement);
    }

    #[Test]
    public function it_should_escaped_characters_in_string_constants(): void
    {
        $parameters = ['foo' => ['bar', 'baz']];
        $sql = <<<SQL
SELECT 'Escaping \' :foo \''
SQL;
        [$statement, $values] = $this->rebuild($sql, $parameters);
        self::assertSame($sql, $statement);

        $sql = <<< 'SQL'
SELECT "Escaping \" :foo \""
SQL;
        [$statement, $values] = $this->rebuild($sql, $parameters);
        self::assertSame($sql, $statement);

        $sql = <<<SQL
SELECT "Escaping \" :foo \"
SQL;
        [$statement, $values] = $this->rebuild($sql, $parameters);
        self::assertSame($sql, $statement);

        $sql = <<<SQL
SELECT "Escaping "" :foo """
SQL;
        [$statement, $values] = $this->rebuild($sql, $parameters);
        self::assertSame($sql, $statement);

        $sql = <<<SQL
SELECT 'Escaping '' :foo '''
SQL;
        [$statement, $values] = $this->rebuild($sql, $parameters);
        self::assertSame($sql, $statement);
    }

    #[Test]
    public function it_should_issue107(): void
    {
        $sql = "UPDATE table SET `value`=:value, `blank`='', `value2` = :value2, `blank2` = '', `value3`=:value3 WHERE id = :id";
        $parameters = [
            'value'  => 'string',
            'value2' => 'string',
            'value3' => 'string',
            'id'     => 1,
        ];
        [$statement, $values] = $this->rebuild($sql, $parameters);
        self::assertSame($statement, $sql);
        self::assertSame($values, $parameters);
    }

    #[Test]
    public function it_should_query_parser_with_backticked_bound_value_and_quotes_values(): void
    {
        $expected = [
            'foo',
            'bar',
            'baz',
            'voo',
        ];

        $this->SQLite->data->getDynamicInsertTable()->push(['c:c' => 'baz']);

        self::assertSame($expected, array_values($this->SQLite->fetchOne('select "foo", \'bar\', `c:c`, :d from data', ['d' => 'voo'])));
    }

    #[Test]
    public function it_should_reassign_query_params(): void
    {
        self::assertSame('1', $this->SQLite->fetchValue('select :id, :id', ['id' => 1, 'id__1' => 2]));
    }

    #[Test]
    public function it_should_reassign_query_params_when_null(): void
    {
        self::assertNull($this->SQLite->fetchValue('select ?', [null]));
    }

    #[Test]
    public function it_should_replace_array_as_parameter(): void
    {
        $parameters = ['foo' => ['bar', 'baz']];
        $sql = 'SELECT :foo';
        [$statement, $values] = $this->rebuild($sql, $parameters);
        $expectedStatement = 'SELECT :foo_0, :foo_1';
        $expectedValues = ['foo_0' => 'bar', 'foo_1' => 'baz'];
        self::assertSame($expectedStatement, $statement);
        self::assertSame($expectedValues, $values);

        $parameters = [['bar', 'baz']];
        $sql = 'SELECT ?';
        [$statement, $values] = $this->rebuild($sql, $parameters);
        $expectedStatement = 'SELECT :__1, :__2';
        $expectedValues = ['__1' => 'bar', '__2' => 'baz'];
        self::assertSame($expectedStatement, $statement);
        self::assertSame($expectedValues, $values);
    }

    #[Test]
    public function it_should_replace_multiple_uses_of_named_parameter(): void
    {
        $parameters = ['foo' => 'bar'];
        $sql = 'SELECT :foo AS a, :foo AS b';
        [$statement, $values] = $this->rebuild($sql, $parameters);
        $expectedStatement = 'SELECT :foo AS a, :foo__1 AS b';
        $expectedValues = ['foo' => 'bar', 'foo__1' => 'bar'];
        self::assertSame($expectedStatement, $statement);
        self::assertSame($expectedValues, $values);
    }

    #[Test]
    public function it_should_replace_numbered_parameter(): void
    {
        $parameters = ['bar', 'baz', null];
        $sql = 'SELECT ? AS a, ? AS b FROM table WHERE id = ?';
        [$statement, $values] = $this->rebuild($sql, $parameters);
        $expectedStatement = 'SELECT :__1 AS a, :__2 AS b FROM table WHERE id = :__3';
        $expectedValues = ['__1' => 'bar', '__2' => 'baz', '__3' => null];
        self::assertSame($expectedStatement, $statement);
        self::assertSame($expectedValues, $values);
    }

    #[Test]
    public function it_should_string_constants(): void
    {
        $parameters = ['foo' => ['bar', 'baz']];
        $sql = <<<SQL
SELECT ':foo'
SQL;
        [$statement, $values] = $this->rebuild($sql, $parameters);
        self::assertSame($sql, $statement);

        $sql = <<<SQL
SELECT 'single quote''s :foo'
SQL;
        [$statement, $values] = $this->rebuild($sql, $parameters);
        self::assertSame($sql, $statement);

        $sql = <<<SQL
SELECT 'multi line string'
':foo'
'bar'
SQL;
        [$statement, $values] = $this->rebuild($sql, $parameters);
        self::assertSame($sql, $statement);
    }

    #[Test]
    public function query_parser_with_backticked_bound_value(): void
    {
        $this->expectException(ColumnDoesNotExistException::class);
        $this->expectExceptionMessage("Column 'c:c' does not exist");

        $this->SQLite->fetchPair('select "a:A", \'B:b\', `c:c`, :d', ['d' => 'foo']);
    }

    #[Test]
    public function query_parser_with_double_quoted_value(): void
    {
        self::assertSame('a:A', $this->SQLite->fetchValue('select "a:A"'));
        self::assertSame('a:A', $this->SQLite->fetchValue('select "a:A"', ['A' => 'foo']));
    }

    #[Test]
    public function query_parser_with_single_quoted_value(): void
    {
        self::assertSame('a:A', $this->SQLite->fetchValue("select 'a:A'"));
        self::assertSame('a:A', $this->SQLite->fetchValue("select 'a:A'", ['A' => 'foo']));
    }

    public function rebuild(string $sql, array $parameters): array
    {
        return (new QueryParser($sql, $parameters))->rebuild();
    }
}
