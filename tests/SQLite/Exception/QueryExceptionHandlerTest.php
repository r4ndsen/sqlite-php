<?php

namespace r4ndsen\SQLite\Exception;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use r4ndsen\SQLite\Connection;
use RuntimeException;

class QueryExceptionHandlerTest extends TestCase
{
    #[Test]
    public function it_should_throw_database_exception(): void
    {
        $this->expectException(DatabaseException::class);

        (new QueryExceptionHandler(new Connection()))->handle(new RuntimeException('foo file is not a database'));
    }
}
