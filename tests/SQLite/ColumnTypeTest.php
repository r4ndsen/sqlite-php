<?php

declare(strict_types=1);

namespace r4ndsen\SQLite;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ColumnTypeTest extends TestCase
{
    #[Test]
    #[DataProvider('providesColumnTypes')]
    public function it_maps_column_type_strings(string $definition, ColumnType $expected): void
    {
        self::assertSame($expected, ColumnType::fromString($definition));
    }

    /** @return iterable<string, array{string, ColumnType}> */
    public static function providesColumnTypes(): iterable
    {
        yield 'numeric alias' => ['numeric', ColumnType::NUMERIC];
        yield 'date affinity' => ['DateTime', ColumnType::NUMERIC];
        yield 'boolean affinity' => ['BOOLEAN', ColumnType::NUMERIC];
        yield 'decimal affinity' => ['Decimal(10,2)', ColumnType::NUMERIC];
        yield 'integer affinity from prefix' => ['integer', ColumnType::INTEGER];
        yield 'integer affinity from suffix' => ['bigint', ColumnType::INTEGER];
        yield 'real affinity from name' => ['REAL', ColumnType::REAL];
        yield 'float affinity' => ['float', ColumnType::REAL];
        yield 'blob affinity' => ['BLOB', ColumnType::BLOB];
        yield 'text fallback' => ['varchar(255)', ColumnType::TEXT];
    }
}
