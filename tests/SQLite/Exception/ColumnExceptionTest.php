<?php

declare(strict_types=1);

namespace r4ndsen\SQLite\Exception;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ColumnExceptionTest extends TestCase
{
    #[Test]
    public function it_exposes_column_name(): void
    {
        $exception = new ColumnDoesNotExistException('my_column');

        self::assertSame('my_column', $exception->getColumn());
        self::assertSame("Column 'my_column' does not exist", $exception->getMessage());
        self::assertSame(0, $exception->getCode());
    }

    #[Test]
    public function it_preserves_previous_exception_code(): void
    {
        $previous = new RuntimeException('boom', 123);
        $exception = new ColumnDoesNotExistException('my_column', $previous);

        self::assertSame(123, $exception->getCode());
        self::assertSame($previous, $exception->getPrevious());
    }

    #[Test]
    public function it_trims_ambiguous_column_names(): void
    {
        $exception = new ColumnNameAmbiguousException('  my_column   ');

        self::assertSame('column name my_column is ambiguous', $exception->getMessage());
    }
}
