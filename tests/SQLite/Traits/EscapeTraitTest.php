<?php

declare(strict_types=1);

namespace r4ndsen\SQLite\Traits;

use PHPUnit\Framework\Attributes\Test;
use r4ndsen\SQLite\TestCase;

final class EscapeTraitTest extends TestCase
{
    use EscapeTrait;

    #[Test]
    public function it_should_escape_backticks(): void
    {
        self::assertSame('`', $this->escape('`'));
        self::assertSame("`'`", $this->backtick("'"));
        self::assertSame('``', $this->backtick(''));
    }

    #[Test]
    public function it_should_escape_columns(): void
    {
        self::assertSame('that``s cool', self::escapeIdentifier('that`s cool'));
        self::assertSame('that``s cool', self::escapeIdentifier('that`s cool'));
        self::assertSame('that``s cool', self::escapeIdentifier('that`s cool'));

        self::assertSame('`that``s cool`', self::backtickIdentifier('that`s cool'));
        self::assertSame('`that``s cool`', self::backtickIdentifier('that`s cool'));
        self::assertSame('`that``s cool`', self::backtickIdentifier('that`s cool'));
    }

    #[Test]
    public function it_should_escape_quotes(): void
    {
        self::assertSame("''", $this->escape("'"));
        self::assertSame('', $this->escape(''));

        self::assertSame("''", self::escapeString("'"));
        self::assertSame("''", self::escapeString("'"));
        self::assertSame("'", self::escapeIdentifier("'"));
        self::assertSame("'", self::escapeIdentifier("'"));
    }

    #[Test]
    public function it_should_escape_trait(): void
    {
        $A = $this->SQLite;
        self::assertSame("''", $A->escape("'"));
        self::assertSame("`'`", $A->backtick("'"));
    }
}
