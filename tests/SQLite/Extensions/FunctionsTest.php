<?php

declare(strict_types=1);

namespace r4ndsen\SQLite\Extensions;

use Closure;
use PHPUnit\Framework\Attributes\Test;
use r4ndsen\SQLite\Exception\FunctionDoesNotExistException;
use r4ndsen\SQLite\Exception\InvalidFunctionException;
use r4ndsen\SQLite\Extensions\Functions\PregReplace;
use r4ndsen\SQLite\TestCase;
use ReflectionFunction;

final class FunctionsTest extends TestCase
{
    #[Test]
    public function it_should_cache_regex_validation_errors(): void
    {
        $regex = new class extends Functions\Regex {
            public function validate(string $pattern): bool
            {
                return $this->validRegex($pattern);
            }
        };

        self::assertFalse($regex->validate('/['));
        self::assertSame(PREG_INTERNAL_ERROR, preg_last_error());

        preg_match('/^$/', '');
        self::assertSame(PREG_NO_ERROR, preg_last_error());

        $regex->validate('/[');
        self::assertSame(PREG_NO_ERROR, preg_last_error());
    }

    #[Test]
    public function it_should_expose_default_preg_replace_limit(): void
    {
        $pregReplace = new PregReplace();
        $callback = $pregReplace->getCallback();

        $reflection = new ReflectionFunction(Closure::fromCallable($callback));

        $parameters = $reflection->getParameters();

        self::assertSame(-1, $parameters[3]->getDefaultValue());
    }

    #[Test]
    public function it_should_function_does_not_exist(): void
    {
        $this->expectException(FunctionDoesNotExistException::class);
        $this->SQLite->exec('select foo()');
    }

    #[Test]
    public function it_should_functions_empty(): void
    {
        self::assertSame(1, $this->SQLite->querySingle('select empty("")'));
        self::assertSame(1, $this->SQLite->querySingle('select empty(null)'));
        self::assertSame(0, $this->SQLite->querySingle('select empty(0)'));
        self::assertSame(0, $this->SQLite->querySingle('select empty("0")'));
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
        self::assertSame(0, $this->SQLite->querySingle('select preg_match("#", "sample")')); // invalid
        self::assertSame(1, $this->SQLite->querySingle('select preg_match("#.*#", null)')); // test regex against null
        self::assertSame(0, $this->SQLite->querySingle('select preg_match("#^a$#", "aa")'));
        self::assertSame(1, $this->SQLite->querySingle('select preg_match("#^a+$#", "aa")'));

        self::assertSame(1, $this->SQLite->querySingle('select preg_match("#^a+$#", "aa") = 1'));
        self::assertSame(0, $this->SQLite->querySingle('select preg_match("#^a+$#", "aa") = 0'));
    }

    #[Test]
    public function it_should_functions_preg_replace(): void
    {
        self::assertSame('value', $this->SQLite->querySingle('select preg_replace("#", "a", "value")')); // invalid regex
        self::assertSame('aaaaa', $this->SQLite->querySingle('select preg_replace("#.#", "a", "value")'));
    }

    #[Test]
    public function it_should_reject_blank_function_identifier(): void
    {
        $functions = new Functions($this->SQLite->getConnection());

        $identifier = str_repeat(' ', 4);

        $failingFunction = new class($identifier) implements Functions\FunctionInterface {
            public function __construct(private readonly string $identifier)
            {
            }

            public function getCallback(): callable
            {
                return static fn () => null;
            }

            public function getIdentifier(): string
            {
                return $this->identifier;
            }
        };

        $this->expectException(InvalidFunctionException::class);
        $this->expectExceptionMessage('Failed to create function: identifier must not be empty');
        $functions->add($failingFunction);
    }

    #[Test]
    public function it_should_throw_invalid_function_when_creation_fails(): void
    {
        $functions = new class($this->SQLite->getConnection()) extends Functions {
            protected function registerFunction(string $identifier, callable $callback): bool
            {
                return false;
            }
        };

        $failingFunction = new class implements Functions\FunctionInterface {
            public function getCallback(): callable
            {
                return static fn () => null;
            }

            public function getIdentifier(): string
            {
                return 'failing_function';
            }
        };

        $this->expectException(InvalidFunctionException::class);
        $this->expectExceptionMessage('Failed to create function: failing_function');

        $functions->add($failingFunction);
    }

    #[Test]
    public function it_should_use_sprintf_callback_function(): void
    {
        self::assertSame('foo 1.23 bar', $this->SQLite->querySingle('select sprintf("%s %.2f %s", "foo", 1.234567, "bar")'));
    }
}
