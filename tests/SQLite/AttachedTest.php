<?php

declare(strict_types=1);

namespace r4ndsen\SQLite;

use PHPUnit\Framework\Attributes\Test;
use r4ndsen\SQLite\Exception\AttachedDatabaseException;

final class AttachedTest extends TestCase
{
    private string $attachedFile = __DIR__ . '/unittest.sqlite';
    private string $encryptedFile = __DIR__ . '/encrypted.sqlite';

    protected function setUp(): void
    {
        parent::setUp();
        $this->tearDown();
    }

    protected function tearDown(): void
    {
        file_exists($this->attachedFile) && unlink($this->attachedFile);
        file_exists($this->encryptedFile) && unlink($this->encryptedFile);
    }

    #[Test]
    public function it_should_attach(): void
    {
        $this->expectException(AttachedDatabaseException::class);

        self::assertTrue($this->SQLite->attach($this->attachedFile, 'one'));
        $this->SQLite->attach($this->attachedFile, 'one');
    }

    #[Test]
    public function it_should_attach_already_in_use(): void
    {
        $this->expectException(AttachedDatabaseException::class);
        $this->expectExceptionMessage('database one is already in use');

        self::assertTrue($this->SQLite->attach($this->attachedFile, 'one'));
        $this->SQLite->attach($this->attachedFile, 'one');
    }

    #[Test]
    public function it_should_attach_already_in_use_case_sensitivity(): void
    {
        $this->expectException(AttachedDatabaseException::class);
        $this->expectExceptionMessage('database One is already in use');

        self::assertTrue($this->SQLite->attach($this->attachedFile, 'one'));
        $this->SQLite->attach($this->attachedFile, 'One');
    }

    #[Test]
    public function it_should_detach(): void
    {
        $this->expectException(AttachedDatabaseException::class);

        self::assertTrue($this->SQLite->attach($this->attachedFile, 'one'));
        self::assertTrue($this->SQLite->detach('one'));
        $this->SQLite->detach('one');
    }

    #[Test]
    public function it_should_get_attached(): void
    {
        $this->expectException(AttachedDatabaseException::class);

        self::assertTrue($this->SQLite->attach($this->attachedFile, 'one'));
        $this->SQLite->getAttached('one');
        self::assertTrue($this->SQLite->detach('one'));
        $this->SQLite->getAttached('one');
    }
}
