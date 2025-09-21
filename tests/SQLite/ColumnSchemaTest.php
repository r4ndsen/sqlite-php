<?php

declare(strict_types=1);

namespace r4ndsen\SQLite;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ColumnSchemaTest extends TestCase
{
    #[Test]
    public function it_handles_nullable_defaults_and_non_private_keys(): void
    {
        $schema = new ColumnSchema(
            columnId: 1,
            name: 'Title',
            type: 'TEXT',
            notNull: 0,
            defaultValue: 'null',
            primaryKey: 0,
        );

        self::assertNull($schema->defaultValue);
        self::assertFalse($schema->notNull);
        self::assertFalse($schema->isPrivateKey);
        self::assertSame(ColumnType::TEXT, $schema->type);
    }
    #[Test]
    public function it_initialises_properties_from_schema_rows(): void
    {
        $schema = new ColumnSchema(
            columnId: 5,
            name: 'Example',
            type: 'INTEGER',
            notNull: 1,
            defaultValue: "'foo'",
            primaryKey: 1,
        );

        self::assertSame(5, $schema->columnId);
        self::assertSame('Example', $schema->name);
        self::assertTrue($schema->notNull);
        self::assertSame('foo', $schema->defaultValue);
        self::assertTrue($schema->isPrivateKey);
        self::assertSame(ColumnType::INTEGER, $schema->type);
    }
}
