<?php

declare(strict_types=1);

namespace r4ndsen\SQLite;

use PHPUnit\Framework\Attributes\Test;
use r4ndsen\SQLite\Exception\ColumnDoesNotExistException;
use r4ndsen\SQLite\Exception\MissingParameterException;

final class QueryParserTest extends TestCase
{
    #[Test]
    public function it_should_double_quoted_identifier(): void
    {
        $parameters = ['foo' => ['bar', 'baz']];
        $sql = <<<SQL
select ":foo"
SQL;
        [$statement, $values] = $this->rebuild($sql, $parameters);
        self::assertSame($sql, $statement);

        $sql = <<<SQL
select "to use double quotes, just double them "" :foo "
SQL;
        [$statement, $values] = $this->rebuild($sql, $parameters);
        self::assertSame($sql, $statement);
    }

    #[Test]
    public function it_should_escaped_characters_in_string_constants(): void
    {
        $parameters = ['foo' => ['bar', 'baz']];

        $sql = <<<SQL
select "Escaping "" :foo """
SQL;
        [$statement, $values] = $this->rebuild($sql, $parameters);
        self::assertSame($sql, $statement);

        $sql = <<<SQL
select 'Escaping '' :foo '''
SQL;
        [$statement, $values] = $this->rebuild($sql, $parameters);
        self::assertSame($sql, $statement);
    }

    #[Test]
    public function it_should_not_treat_backslashes_as_escape_characters(): void
    {
        // sqlite only escapes quotes by doubling them, a backslash is a regular character
        $sql = "select 'C:\\' as p, :x as x, 'y' as y";
        [$statement, $values] = $this->rebuild($sql, ['x' => 42]);
        self::assertSame("select 'C:\\' as p, :x as x, 'y' as y", $statement);
        self::assertSame(['x' => 42], $values);

        self::assertSame(['p' => 'C:\\', 'x' => 42, 'y' => 'y'], $this->SQLite->fetchOne($sql, ['x' => 42]));
        self::assertSame(['p' => 'C:\\', 'x' => 42], $this->SQLite->fetchOne('select "C:\\" as p, :x as x', ['x' => 42]));
    }

    #[Test]
    public function it_should_ignore_placeholders_and_quotes_in_comments(): void
    {
        $sql = "select :x as x -- don't :y\n, 'z' /* it's :y */ as z, [a:b] as w";
        [$statement, $values] = $this->rebuild($sql, ['x' => 1]);
        self::assertSame($sql, $statement);
        self::assertSame(['x' => 1], $values);

        self::assertSame(['x' => 1, 'z' => 'z', 'a:b' => null], $this->SQLite->fetchOne("select :x as x -- don't :y\n, 'z' as z, null as [a:b]", ['x' => 1]));
        self::assertSame(['x' => 1, 'z' => 'z'], $this->SQLite->fetchOne("select 1 as x -- don't\n, :z as z", ['z' => 'z']));
        self::assertSame(['x' => 1, 'z' => 'z'], $this->SQLite->fetchOne("select 1 as x /* don't */, :z as z", ['z' => 'z']));
    }

    #[Test]
    public function it_should_replace_numbered_parameters_with_index(): void
    {
        [$statement, $values] = $this->rebuild('select ?2, ?1, ?', ['a', 'b', 'c']);
        self::assertSame('select :__1, :__2, :__3', $statement);
        self::assertSame(['__1' => 'b', '__2' => 'a', '__3' => 'c'], $values);

        self::assertSame([7, 7], $this->SQLite->fetchPlain('select ?1, ?1', [7])[0]);
    }

    #[Test]
    public function it_should_throw_on_unbound_parameters(): void
    {
        $this->expectException(MissingParameterException::class);
        $this->expectExceptionMessage('The statement expects 1 parameter(s) but 0 were bound');

        $this->SQLite->fetchValue('select @foo', ['foo' => 1]);
    }

    #[Test]
    public function it_should_issue107(): void
    {
        $sql = "update table set `value`=:value, `blank`='', `value2` = :value2, `blank2` = '', `value3`=:value3 where id = :id";
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

        $res = $this->SQLite->fetchOne('select "foo", \'bar\', `c:c`, :d from data', ['d' => 'voo']);

        self::assertSame($expected, array_values($res ?? []));
    }

    #[Test]
    public function it_should_reassign_query_params(): void
    {
        self::assertSame(1, $this->SQLite->fetchValue('select :id, :id', ['id' => 1, 'id__1' => 2]));
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
        $sql = 'select :foo';
        [$statement, $values] = $this->rebuild($sql, $parameters);
        $expectedStatement = 'select :foo_0, :foo_1';
        $expectedValues = ['foo_0' => 'bar', 'foo_1' => 'baz'];
        self::assertSame($expectedStatement, $statement);
        self::assertSame($expectedValues, $values);

        $parameters = [['bar', 'baz']];
        $sql = 'select ?';
        [$statement, $values] = $this->rebuild($sql, $parameters);
        $expectedStatement = 'select :__1, :__2';
        $expectedValues = ['__1' => 'bar', '__2' => 'baz'];
        self::assertSame($expectedStatement, $statement);
        self::assertSame($expectedValues, $values);
    }

    #[Test]
    public function it_should_replace_multiple_uses_of_named_parameter(): void
    {
        $parameters = ['foo' => 'bar'];
        $sql = 'select :foo as a, :foo as b';
        [$statement, $values] = $this->rebuild($sql, $parameters);
        $expectedStatement = 'select :foo as a, :foo__1 as b';
        $expectedValues = ['foo' => 'bar', 'foo__1' => 'bar'];
        self::assertSame($expectedStatement, $statement);
        self::assertSame($expectedValues, $values);
    }

    #[Test]
    public function it_should_replace_numbered_parameter(): void
    {
        $parameters = ['bar', 'baz', null];
        $sql = 'select ? as a, ? as b from table where id = ?';
        [$statement, $values] = $this->rebuild($sql, $parameters);
        $expectedStatement = 'select :__1 as a, :__2 as b from table where id = :__3';
        $expectedValues = ['__1' => 'bar', '__2' => 'baz', '__3' => null];
        self::assertSame($expectedStatement, $statement);
        self::assertSame($expectedValues, $values);
    }

    #[Test]
    public function it_should_string_constants(): void
    {
        $parameters = ['foo' => ['bar', 'baz']];
        $sql = <<<SQL
select ':foo'
SQL;
        [$statement, $values] = $this->rebuild($sql, $parameters);
        self::assertSame($sql, $statement);

        $sql = <<<SQL
select 'single quote''s :foo'
SQL;
        [$statement, $values] = $this->rebuild($sql, $parameters);
        self::assertSame($sql, $statement);

        $sql = <<<SQL
select 'multi line string'
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
        $qp = new QueryParser($sql, $parameters);

        return [$qp->getStatement(), $qp->getValues()];
    }
}
