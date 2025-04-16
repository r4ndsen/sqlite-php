<?php

declare(strict_types=1);

namespace r4ndsen\SQLite\Extensions;

use PHPUnit\Framework\Attributes\Test;
use r4ndsen\SQLite\Exception\FunctionDoesNotExistException;
use r4ndsen\SQLite\TestCase;
use SQLite3;

final class FunctionsTest extends TestCase
{
    #[Test]
    public function it_should_function_does_not_exist(): void
    {
        $this->expectException(FunctionDoesNotExistException::class);
        $this->SQLite->exec('select foo()');
    }

    #[Test]
    public function it_should_function_does_not_exist2(): void
    {
        $this->expectException(FunctionDoesNotExistException::class);
        $this->SQLite->perform('select foo()');
    }

    #[Test]
    public function it_should_functions_empty(): void
    {
        self::assertTrue((bool) $this->SQLite->querySingle('select empty("")'));
        self::assertTrue((bool) $this->SQLite->querySingle('select empty(null)'));
        self::assertFalse((bool) $this->SQLite->querySingle('select empty(0)'));
        self::assertFalse((bool) $this->SQLite->querySingle('select empty("0")'));
    }

    #[Test]
    public function it_should_functions_md5(): void
    {
        self::assertSame(md5('value'), $this->SQLite->querySingle('select md5("value")'));
        self::assertSame(md5(''), $this->SQLite->querySingle('select md5("")'));
    }

    #[Test]
    public function it_should_functions_preg_match(): void
    {
        self::assertFalse((bool) $this->SQLite->querySingle('select preg_match("#", "sample")')); // invalid
        self::assertTrue((bool) $this->SQLite->querySingle('select preg_match("#.*#", null)')); // test regex against null
        self::assertFalse((bool) $this->SQLite->querySingle('select preg_match("#^a$#", "aa")'));
        self::assertTrue((bool) $this->SQLite->querySingle('select preg_match("#^a+$#", "aa")'));

        self::assertSame(0, $this->SQLite->querySingle('select preg_match("#", "sample")')); // invalid
        self::assertSame(1, $this->SQLite->querySingle('select preg_match("#.*#", null)')); // test regex against null
        self::assertSame(0, $this->SQLite->querySingle('select preg_match("#^a$#", "aa")'));
        self::assertSame(1, $this->SQLite->querySingle('select preg_match("#^a+$#", "aa")'));

        self::assertSame(1, $this->SQLite->querySingle('select preg_match("#^a+$#", "aa") = 1'));
        self::assertSame(0, $this->SQLite->querySingle('select preg_match("#^a+$#", "aa") = 0'));
    }

    #[Test]
    public function it_should_functions_preg_match_native_behavior(): void
    {
        $s = new SQLite3(':memory:');
        $s->createFunction('pm', static fn ($pattern, $value) => @preg_match($pattern, (string) $value));

        self::assertSame(1, $s->querySingle('select pm("#^a+$#", "aa")'));
        self::assertSame(0, $s->querySingle('select pm("#^a+$#", "b")'));
        self::assertSame('', $s->querySingle('select pm("#^a", "b")'));

        self::assertSame(0, $s->querySingle('select pm("#^a", "b") = 0'), 'evaluating invalid regex against int 0');
        self::assertSame(1, $s->querySingle('select pm("#^a+$#", "aa") = 1'), 'evaluating match against int 1');
        self::assertSame(1, $s->querySingle('select pm("#^a+$#", "b") = 0'), 'evaluating nomatch against int 0');
        self::assertSame(0, $s->querySingle('select pm("#^a", "b") = 0'), 'invalid regex');

        self::assertSame(1, $s->querySingle('select true'));
        self::assertSame(0, $s->querySingle('select false'));

        self::assertSame(1, $s->querySingle('select 1 = 1'));
        self::assertSame(0, $s->querySingle('select 1 = 0'));

        self::assertSame(0, $s->querySingle('select 1 = false'));
        self::assertSame(1, $s->querySingle('select 0 = false'));

        self::assertSame(1, $s->querySingle('select 1 = true'));
        self::assertSame(0, $s->querySingle('select 0 = true'));

        self::assertSame(0, $s->querySingle("select 0 = ''"));
        self::assertSame(0, $s->querySingle("select 0 = '0'"), 'integer is not equivalent to string');
        self::assertSame(0, $s->querySingle("select 1 = '1'"), 'integer is not equivalent to string');

        self::assertSame(0, $s->querySingle("select false = ''"), 'boolean is not equivalent to string');
        self::assertSame(0, $s->querySingle("select true = '1'"), 'boolean is not equivalent to string');
        self::assertSame(0, $s->querySingle("select false = '1'"), 'boolean is not equivalent to string');
    }

    #[Test]
    public function it_should_functions_preg_replace(): void
    {
        self::assertSame('value', $this->SQLite->querySingle('select preg_replace("#", "a", "value")')); // invalid regex
        self::assertSame('aaaaa', $this->SQLite->querySingle('select preg_replace("#.#", "a", "value")'));
    }
}
